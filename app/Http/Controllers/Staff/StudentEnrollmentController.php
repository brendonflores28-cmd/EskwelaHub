<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\EnrollmentRequest;
use App\Http\Requests\Staff\GraduationRequest;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** Enrollment history (one row per school year) and graduation data of a student. */
class StudentEnrollmentController extends Controller
{
    public function store(EnrollmentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        $sectionId = null;
        if (! empty($data['section'])) {
            $sectionId = Section::firstOrCreate([
                'school_year_id' => $data['school_year_id'],
                'grade_level' => $data['grade_level'],
                'name' => $data['section'],
            ])->id;
        }

        // One enrollment per student per school year: saving again updates it.
        $student->enrollments()->updateOrCreate(
            ['school_year_id' => $data['school_year_id']],
            [
                'section_id' => $sectionId,
                'grade_level' => $data['grade_level'],
                'curriculum' => $data['curriculum'],
                'status' => $data['status'],
                'general_average' => $data['general_average'] ?? null,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Enrollment saved.']);

        return back();
    }

    public function destroy(Student $student, StudentEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('update', $student);
        abort_unless($enrollment->student_id === $student->id, 404);

        $enrollment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Enrollment removed.']);

        return back();
    }

    public function graduation(GraduationRequest $request, Student $student): RedirectResponse
    {
        $student->graduation()->updateOrCreate([], $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Graduation data saved.']);

        return back();
    }
}
