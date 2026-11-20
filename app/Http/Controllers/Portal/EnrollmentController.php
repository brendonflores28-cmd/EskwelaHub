<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EnrollmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentFormRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Survey;
use App\Services\Enrollment\EnrollmentWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Parent or learner: fill in the online enrollment form and follow its status. */
class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = EnrollmentApplication::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('portal/enrollments/Index', [
            'applications' => EnrollmentResource::collection($applications),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('portal/enrollments/Create', [
            'schoolYear' => SchoolYear::where('is_current', true)->value('label'),
            'types' => collect(EnrollmentType::cases())->map(fn (EnrollmentType $t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function store(EnrollmentFormRequest $request, EnrollmentWorkflow $workflow): RedirectResponse
    {
        $application = $workflow->submit($request->user(), $request->fields(), $request->documents());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Form {$application->reference_no} sent."]);

        return to_route('portal.enrollments.show', $application);
    }

    public function show(EnrollmentApplication $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['documents', 'schoolYear', 'student']);

        return Inertia::render('portal/enrollments/Show', [
            'application' => new EnrollmentResource($application),
            // Ask for feedback once, after the registrar decided.
            'canSurvey' => ! $application->status->isOpen()
                && ! Survey::where('user_id', request()->user()->id)->where('enrollment_application_id', $application->id)->exists(),
        ]);
    }
}
