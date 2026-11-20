<?php

namespace App\Services\Requests;

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Jobs\GenerateDocumentPdf;
use App\Models\DocumentRequest;
use App\Models\Student;
use App\Models\User;
use App\Notifications\RequestStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes a request's status.
 *
 * Every change is checked against RequestStatus::allowedNext(), written to the
 * status history, logged in the audit log and announced to the requester.
 * The row is locked while it changes, so two clicks cannot approve twice.
 */
class RequestWorkflow
{
    /** A user (with an approved link to the student) asks for a document. */
    public function submit(User $requester, Student $student, array $data): DocumentRequest
    {
        $request = DB::transaction(function () use ($requester, $student, $data): DocumentRequest {
            $request = DocumentRequest::create([
                'user_id' => $requester->id,
                'student_id' => $student->id,
                'document_type' => DocumentType::from($data['document_type']),
                'purpose' => $data['purpose'],
                'copies' => $data['copies'] ?? 1,
            ]);

            $this->record($request, null, RequestStatus::Submitted, $requester, null);
            $this->audit($request, 'requested', 'Requested '.$request->document_type->label(), $requester);

            return $request;
        });

        $requester->notify(new RequestStatusChanged($request, RequestStatus::Submitted));

        return $request;
    }

    public function startReview(DocumentRequest $request, User $staff): DocumentRequest
    {
        return $this->transition($request, RequestStatus::UnderReview, $staff, null, 'reviewed');
    }

    /** Approving also queues the PDF job, which moves the request on to processing and ready. */
    public function approve(DocumentRequest $request, User $staff, ?string $note = null): DocumentRequest
    {
        $request = $this->transition($request, RequestStatus::Approved, $staff, $note, 'approved');

        GenerateDocumentPdf::dispatch($request->id)->afterCommit();

        return $request;
    }

    public function reject(DocumentRequest $request, User $staff, string $reason): DocumentRequest
    {
        return $this->transition($request, RequestStatus::Rejected, $staff, $reason, 'rejected');
    }

    /** Used by the PDF job (no staff user: the system acts). */
    public function markProcessing(DocumentRequest $request): DocumentRequest
    {
        return $this->transition($request, RequestStatus::Processing, null, null, 'processing');
    }

    public function markReady(DocumentRequest $request): DocumentRequest
    {
        return $this->transition($request, RequestStatus::Ready, null, null, 'ready');
    }

    public function release(DocumentRequest $request, User $staff): DocumentRequest
    {
        return $this->transition($request, RequestStatus::Released, $staff, null, 'released');
    }

    /** Staff retry when the PDF job failed or did not run. Does not change the status. */
    public function regenerate(DocumentRequest $request, User $staff): void
    {
        if (! in_array($request->status, [RequestStatus::Approved, RequestStatus::Processing], true)) {
            throw new InvalidRequestTransition('Only an approved request can be generated again.');
        }

        $this->audit($request, 'regenerate', 'Asked to generate the document again', $staff);
        GenerateDocumentPdf::dispatch($request->id);
    }

    private function transition(
        DocumentRequest $request,
        RequestStatus $to,
        ?User $by,
        ?string $note,
        string $event,
    ): DocumentRequest {
        $updated = DB::transaction(function () use ($request, $to, $by, $note, $event): DocumentRequest {
            // Lock the row: re-read the status inside the lock, not from the caller's copy.
            $locked = DocumentRequest::query()->lockForUpdate()->findOrFail($request->id);
            $from = $locked->status;

            if (! $from->canMoveTo($to)) {
                throw InvalidRequestTransition::between($from, $to);
            }

            $locked->status = $to;
            if ($note !== null && in_array($to, [RequestStatus::Approved, RequestStatus::Rejected], true)) {
                $locked->remarks = $note;
            }
            if ($by && in_array($to, [RequestStatus::UnderReview, RequestStatus::Approved, RequestStatus::Rejected], true)) {
                $locked->reviewed_by = $by->id;
                $locked->reviewed_at = now();
            }
            if ($to === RequestStatus::Released) {
                $locked->released_by = $by?->id;
                $locked->released_at = now();
            }
            $locked->save();

            $this->record($locked, $from, $to, $by, $note);
            $this->audit($locked, $event, "Request {$from->label()} to {$to->label()}", $by);

            return $locked;
        });

        // Only the changes the requester cares about. "Processing" is internal noise.
        if ($to !== RequestStatus::Processing) {
            $updated->user->notify(new RequestStatusChanged($updated, $to));
        }

        return $updated;
    }

    private function record(DocumentRequest $request, ?RequestStatus $from, RequestStatus $to, ?User $by, ?string $note): void
    {
        $request->histories()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $by?->id,
            'note' => $note,
        ]);
    }

    private function audit(DocumentRequest $request, string $event, string $description, ?User $by): void
    {
        activity('requests')
            ->performedOn($request)
            ->causedBy($by)
            ->event($event)
            ->withProperties(['request_no' => $request->request_no, 'ip' => request()->ip()])
            ->log($description);
    }
}
