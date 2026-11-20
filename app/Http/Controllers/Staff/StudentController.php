<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Curriculum;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Search\StudentSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/** Registrar / staff screens for student records: search, view, create, edit. */
class StudentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, StudentSearch $search): Response
    {
        $this->authorize('viewAny', Student::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'school_year_id' => ['nullable', 'integer'],
            'grade_level' => ['nullable', 'integer', 'between:7,12'],
            'status' => ['nullable', 'string'],
        ]);

        return Inertia::render('staff/students/Index', [
            'students' => StudentResource::collection($search->search($filters)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'school_year_id' => $filters['school_year_id'] ?? null,
                'grade_level' => $filters['grade_level'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(['id', 'label']),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('staff/students/Form', [
            'student' => null,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = Student::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student record created.']);

        return to_route('staff.students.show', $student);
    }

    public function show(Student $student): Response
    {
        $this->authorize('view', $student);
        $this->audit->viewed($student, 'Viewed student record');

        $student->load(['enrollments.schoolYear', 'enrollments.section', 'graduation.schoolYear']);

        return Inertia::render('staff/students/Show', [
            'student' => new StudentResource($student),
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(['id', 'label']),
            'statuses' => $this->statusOptions(),
            'curricula' => collect(Curriculum::cases())
                ->map(fn (Curriculum $c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        return Inertia::render('staff/students/Form', [
            'student' => new StudentResource($student),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student record updated.']);

        return to_route('staff.students.show', $student);
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->delete(); // soft delete: the row stays in the database

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student record archived.']);

        return to_route('staff.students.index');
    }

    /** @return Collection<int, array{value: string, label: string}> */
    private function statusOptions(): Collection
    {
        return collect(StudentStatus::cases())
            ->map(fn (StudentStatus $s) => ['value' => $s->value, 'label' => $s->label()]);
    }
}
