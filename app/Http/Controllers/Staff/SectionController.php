<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Strand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\SectionRequest;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class sections: list them per grade, see each class list and move learners
 * in and out. Reading needs students.view; changes need sections.manage.
 */
class SectionController extends Controller
{
    public function index(Request $request): Response
    {
        $yearId = $request->validate(['school_year_id' => ['nullable', 'integer']])['school_year_id']
            ?? SchoolYear::where('is_current', true)->value('id')
            ?? SchoolYear::orderByDesc('start_year')->value('id');

        $sections = Section::query()
            ->where('school_year_id', $yearId)
            ->withCount('enrollments')
            ->orderBy('grade_level')->orderBy('name')
            ->get();

        // Per grade: learners enrolled that year and how many still have no section.
        $enrolled = StudentEnrollment::query()->where('school_year_id', $yearId)->get(['grade_level', 'section_id'])->groupBy('grade_level');

        $grades = collect(range(7, 12))->map(fn (int $grade) => [
            'grade' => $grade,
            'enrolled' => $enrolled->get($grade, collect())->count(),
            'unassigned' => $enrolled->get($grade, collect())->whereNull('section_id')->count(),
            'sections' => $sections->where('grade_level', $grade)->map(fn (Section $s) => $this->sectionArray($s))->values(),
        ]);

        return Inertia::render('staff/sections/Index', [
            'schoolYearId' => (int) $yearId,
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(['id', 'label']),
            'grades' => $grades,
            'canManage' => $request->user()->can('sections.manage'),
            'strands' => Strand::options(), // for the "Add section" form of Grades 11 and 12
        ]);
    }

    public function store(SectionRequest $request): RedirectResponse
    {
        $section = Section::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$section->display_name} created."]);

        return to_route('staff.sections.index', ['school_year_id' => $section->school_year_id]);
    }

    /** Class list of one section, plus the learners of the same grade who have no section yet. */
    public function show(Section $section): Response
    {
        $section->loadCount('enrollments')->load('schoolYear');

        $members = $section->enrollments()->with('student')->get()
            ->sortBy(fn (StudentEnrollment $e) => $e->student->name_key)
            ->map(fn (StudentEnrollment $e) => $this->learnerArray($e))
            ->values();

        $waiting = StudentEnrollment::query()
            ->where('school_year_id', $section->school_year_id)
            ->where('grade_level', $section->grade_level)
            ->whereNull('section_id')
            // A senior high section only takes learners of its own strand (or with no strand chosen yet).
            ->when($section->strand, fn ($q, $strand) => $q->where(fn ($w) => $w->whereNull('strand')->orWhere('strand', $strand)))
            ->with('student')
            ->get()
            ->sortBy(fn (StudentEnrollment $e) => $e->student->name_key)
            ->map(fn (StudentEnrollment $e) => $this->learnerArray($e))
            ->values();

        return Inertia::render('staff/sections/Show', [
            'section' => $this->sectionArray($section) + ['school_year' => $section->schoolYear->label, 'school_year_id' => $section->school_year_id],
            'members' => $members,
            'waiting' => $waiting,
            'canManage' => request()->user()->can('sections.manage'),
        ]);
    }

    public function update(SectionRequest $request, Section $section): RedirectResponse
    {
        $capacity = $request->validated('capacity');
        if ($capacity !== null && $capacity < $section->enrollments()->count()) {
            throw ValidationException::withMessages(['capacity' => 'The capacity is lower than the learners already in this section.']);
        }

        // The strand of a section that already has learners cannot change under them.
        $newStrand = $request->validated('strand');
        if (($newStrand ?? null) !== $section->strand?->value && $section->enrollments()->exists()) {
            throw ValidationException::withMessages(['strand' => 'Move the learners out before changing the strand of this section.']);
        }

        $section->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Section updated.']);

        return back();
    }

    public function destroy(Request $request, Section $section): RedirectResponse
    {
        abort_unless($request->user()->can('sections.manage'), 403);

        if ($section->enrollments()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Move the learners out first. A section with learners cannot be deleted.']);

            return back();
        }

        $yearId = $section->school_year_id;
        $section->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Section deleted.']);

        return to_route('staff.sections.index', ['school_year_id' => $yearId]);
    }

    /** Put learners (their enrollments of this school year and grade) into the section. */
    public function assign(Request $request, Section $section): RedirectResponse
    {
        abort_unless($request->user()->can('sections.manage'), 403);

        $ids = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer'],
        ])['enrollment_ids'];

        DB::transaction(function () use ($section, $ids): void {
            // Lock the section so two registrars cannot both fill the last seat.
            $locked = Section::query()->lockForUpdate()->findOrFail($section->id);

            // Only enrollments of the same school year and grade can join (a Grade 8 learner cannot be put in Grade 7).
            $enrollments = StudentEnrollment::query()
                ->whereIn('id', $ids)
                ->where('school_year_id', $locked->school_year_id)
                ->where('grade_level', $locked->grade_level)
                ->get();

            if ($enrollments->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['enrollment_ids' => 'Some learners are not enrolled in this grade and school year.']);
            }

            $moving = $enrollments->where('section_id', '!=', $locked->id)->count();
            $seats = $locked->seatsLeft();
            if ($seats !== null && $moving > $seats) {
                throw ValidationException::withMessages(['enrollment_ids' => "Only {$seats} place(s) left in this section."]);
            }

            // Senior high: the learner's strand must be the section's strand. A learner with no strand yet takes it.
            if ($locked->strand && $enrollments->contains(fn (StudentEnrollment $e) => $e->strand !== null && $e->strand !== $locked->strand)) {
                throw ValidationException::withMessages(['enrollment_ids' => "Only {$locked->strand->short()} learners can join this section."]);
            }

            StudentEnrollment::whereIn('id', $enrollments->modelKeys())->update([
                'section_id' => $locked->id,
                ...($locked->strand ? ['strand' => $locked->strand->value] : []),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => count($ids).' learner(s) added to the section.']);

        return back();
    }

    /** Take learners out of the section (they keep their enrollment, just without a section). */
    public function unassign(Request $request, Section $section): RedirectResponse
    {
        abort_unless($request->user()->can('sections.manage'), 403);

        $ids = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer'],
        ])['enrollment_ids'];

        $section->enrollments()->whereIn('id', $ids)->update(['section_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Learner(s) removed from the section.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function sectionArray(Section $s): array
    {
        return [
            'id' => $s->id,
            'grade_level' => $s->grade_level,
            'strand' => $s->strand?->value,
            'strand_short' => $s->strand?->short(),
            'name' => $s->name,
            'adviser_name' => $s->adviser_name,
            'capacity' => $s->capacity,
            'learners' => $s->enrollments_count ?? 0,
        ];
    }

    /** @return array<string, mixed> */
    private function learnerArray(StudentEnrollment $e): array
    {
        return [
            'enrollment_id' => $e->id,
            'student_id' => $e->student_id,
            'full_name' => $e->student->full_name,
            'lrn' => $e->student->lrn,
            'sex' => $e->student->sex,
            'status' => $e->status->value,
        ];
    }
}
