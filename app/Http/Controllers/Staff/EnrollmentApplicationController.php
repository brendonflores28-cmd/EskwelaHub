<?php

namespace App\Http\Controllers\Staff;

use App\Enums\EnrollmentStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\EnrollmentApplication;
use App\Services\Enrollment\EnrollmentWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Registrar: review online enrollment forms. */
class EnrollmentApplicationController extends Controller
{
    public function __construct(private readonly EnrollmentWorkflow $workflow) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EnrollmentApplication::class);

        $status = $request->validate(['status' => ['nullable', 'string']])['status'] ?? null;

        $applications = EnrollmentApplication::query()
            ->with(['schoolYear', 'user'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('staff/enrollments/Index', [
            'applications' => EnrollmentResource::collection($applications),
            'filters' => ['status' => $status],
            'statuses' => collect(EnrollmentStatus::cases())->map(fn (EnrollmentStatus $s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function show(EnrollmentApplication $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['documents', 'schoolYear', 'user', 'student']);

        return Inertia::render('staff/enrollments/Show', [
            'application' => new EnrollmentResource($application),
            'open' => $application->status->isOpen(),
        ]);
    }

    public function review(EnrollmentApplication $application): RedirectResponse
    {
        return $this->act($application, 'Review started.', fn () => $this->workflow->startReview($application, auth()->user()));
    }

    public function approve(Request $request, EnrollmentApplication $application): RedirectResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        return $this->act($application, 'Approved. The student record was created and linked.', fn () => $this->workflow->approve($application, auth()->user(), $note));
    }

    public function reject(Request $request, EnrollmentApplication $application): RedirectResponse
    {
        $reason = $request->validate(['note' => ['required', 'string', 'max:500']])['note'];

        return $this->act($application, 'Enrollment rejected.', fn () => $this->workflow->reject($application, auth()->user(), $reason));
    }

    private function act(EnrollmentApplication $application, string $success, callable $action): RedirectResponse
    {
        $this->authorize('process', $application);

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
