<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentType;
use App\Enums\LinkStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use App\Models\Student;
use App\Services\Requests\RequestWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Student / parent / alumni: ask for a document and follow the request. */
class DocumentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $requests = DocumentRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('student')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('portal/requests/Index', [
            'requests' => DocumentRequestResource::collection($requests),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('portal/requests/Create', [
            'students' => $this->linkedStudents($request),
            'types' => collect(DocumentType::cases())->map(fn (DocumentType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    public function store(Request $request, RequestWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'purpose' => ['required', 'string', 'max:255'],
            'copies' => ['required', 'integer', 'between:1,5'],
        ]);

        // Only for a student whose link to this account staff approved.
        $student = Student::findOrFail($data['student_id']);
        abort_unless($request->user()->canAccessStudent($student), 403);

        $documentRequest = $workflow->submit($request->user(), $student, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Request {$documentRequest->request_no} sent."]);

        return to_route('portal.requests.show', $documentRequest);
    }

    public function show(DocumentRequest $documentRequest): Response
    {
        $this->authorize('view', $documentRequest);

        $documentRequest->load(['student', 'histories.changer', 'document']);

        return Inertia::render('portal/requests/Show', [
            'request' => new DocumentRequestResource($documentRequest),
            'canDownload' => request()->user()->can('download', $documentRequest) && $documentRequest->document !== null,
        ]);
    }

    /** @return Collection<int, array{id: int, name: string}> */
    private function linkedStudents(Request $request)
    {
        return $request->user()->studentLinks()
            ->where('status', LinkStatus::Approved)
            ->with('student')
            ->get()
            ->map(fn ($link) => ['id' => $link->student_id, 'name' => $link->student->full_name]);
    }
}
