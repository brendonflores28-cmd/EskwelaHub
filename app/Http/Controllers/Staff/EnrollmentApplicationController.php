<?php

namespace App\Http\Controllers\Staff;

use App\Enums\EnrollmentStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\EnrollmentApplication;
use App\Models\Section;
use App\Services\Duplicates\DuplicateDetector;
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

    public function show(EnrollmentApplication $application, DuplicateDetector $detector): Response
    {
        $this->authorize('view', $application);

        $application->load(['documents', 'schoolYear', 'user', 'student']);

        return Inertia::render('staff/enrollments/Show', [
            'application' => new EnrollmentResource($application),
            'open' => $application->status->isOpen(),
            // Sections of the learner's grade this school year, to choose one when approving.
            'sections' => Section::where('school_year_id', $application->school_year_id)
                ->where('grade_level', $application->grade_level)->withCount('enrollments')->orderBy('name')->get()
                ->map(fn (Section $s) => [
                    'id' => $s->id,
                    'label' => $s->name.($s->capacity ? " ({$s->enrollments_count}/{$s->capacity})" : " ({$s->enrollments_count})"),
                    'full' => $s->capacity !== null && $s->enrollments_count >= $s->capacity,
                ]),
            // Records already in the system that look like this learner (avoid creating a duplicate).
            'similar' => $application->status->isOpen()
                ? $detector->similarTo($application->last_name, $application->first_name, $application->birthdate->toDateString())
                    ->map(fn ($s) => ['id' => $s->id, 'full_name' => $s->full_name, 'lrn' => $s->lrn, 'birthdate' => $s->birthdate->toDateString()])
                : [],
        ]);
    }

    public function review(EnrollmentApplication $application): RedirectResponse
    {
        return $this->act($application, 'Review started.', fn () => $this->workflow->startReview($application, auth()->user()));
    }

    public function approve(Request $request, EnrollmentApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'section_id' => ['nullable', 'integer'], // optional: put the learner straight into a class section
        ]);

        return $this->act($application, 'Approved. The student record was created and linked.', fn () => $this->workflow->approve(
            $application, auth()->user(), $data['note'] ?? null, $data['section_id'] ?? null,
        ));
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
