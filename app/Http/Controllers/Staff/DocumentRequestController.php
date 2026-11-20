<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use App\Services\Requests\RequestWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Registrar screens: list requests and move them through the workflow. */
class DocumentRequestController extends Controller
{
    public function __construct(private readonly RequestWorkflow $workflow) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $requests = DocumentRequest::query()
            ->with(['student', 'user'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('request_no', 'like', '%'.$term.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('staff/requests/Index', [
            'requests' => DocumentRequestResource::collection($requests),
            'filters' => ['status' => $filters['status'] ?? null, 'q' => $filters['q'] ?? ''],
            'statuses' => collect(RequestStatus::cases())->map(fn (RequestStatus $s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function show(DocumentRequest $documentRequest): Response
    {
        $this->authorize('view', $documentRequest);

        $documentRequest->load(['student', 'user', 'histories.changer', 'document']);

        return Inertia::render('staff/requests/Show', [
            'request' => new DocumentRequestResource($documentRequest),
            // The page shows only the buttons that make sense for the current status.
            'actions' => collect(RequestStatus::cases())
                ->filter(fn (RequestStatus $s) => $documentRequest->status->canMoveTo($s))
                ->map(fn (RequestStatus $s) => $s->value)->values(),
            'canDownload' => request()->user()->can('download', $documentRequest) && $documentRequest->document !== null,
            'canRegenerate' => in_array($documentRequest->status, [RequestStatus::Approved, RequestStatus::Processing], true),
        ]);
    }

    public function review(DocumentRequest $documentRequest): RedirectResponse
    {
        return $this->act($documentRequest, 'Review started.', fn () => $this->workflow->startReview($documentRequest, auth()->user()));
    }

    public function approve(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        return $this->act($documentRequest, 'Approved. The document is being prepared.', fn () => $this->workflow->approve($documentRequest, auth()->user(), $note));
    }

    public function reject(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $reason = $request->validate(['note' => ['required', 'string', 'max:500']])['note'];

        return $this->act($documentRequest, 'Request rejected.', fn () => $this->workflow->reject($documentRequest, auth()->user(), $reason));
    }

    public function release(DocumentRequest $documentRequest): RedirectResponse
    {
        return $this->act($documentRequest, 'Released to the requester.', fn () => $this->workflow->release($documentRequest, auth()->user()));
    }

    public function regenerate(DocumentRequest $documentRequest): RedirectResponse
    {
        return $this->act($documentRequest, 'Generating the document again.', fn () => $this->workflow->regenerate($documentRequest, auth()->user()));
    }

    /** Runs one workflow action after the policy check and turns a bad transition into a friendly message. */
    private function act(DocumentRequest $documentRequest, string $success, callable $action): RedirectResponse
    {
        $this->authorize('process', $documentRequest);

        try {
            $action();
        } catch (InvalidRequestTransition $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $success]);

        return back();
    }
}
