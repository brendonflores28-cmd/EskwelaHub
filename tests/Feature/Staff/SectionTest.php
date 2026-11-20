<?php

use App\Enums\Curriculum;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Enrollment\EnrollmentWorkflow;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->year = SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    $this->staff = staffUser();
});

/** A learner enrolled in the test school year. */
function enrolledLearner($test, int $grade = 7, ?Section $section = null): StudentEnrollment
{
    $student = Student::factory()->create(['middle_name' => null, 'suffix' => null]);

    return $student->enrollments()->create([
        'school_year_id' => $test->year->id, 'grade_level' => $grade, 'curriculum' => 'k12',
        'status' => 'enrolled', 'section_id' => $section?->id,
    ]);
}

function newSection($test, array $overrides = []): Section
{
    return Section::create(array_merge(['school_year_id' => $test->year->id, 'grade_level' => 7, 'name' => 'Rizal'], $overrides));
}

it('creates a section with adviser and capacity', function () {
    $this->actingAs($this->staff)->post(route('staff.sections.store'), [
        'school_year_id' => $this->year->id, 'grade_level' => 7, 'name' => 'Rizal', 'adviser_name' => 'Ms. Reyes', 'capacity' => 40,
    ])->assertRedirect();

    $section = Section::firstOrFail();
    expect($section->display_name)->toBe('Grade 7 - Rizal')->and($section->capacity)->toBe(40);
});

it('does not allow the same section name twice in one grade, but allows it in another grade', function () {
    newSection($this);

    $this->actingAs($this->staff)->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 7, 'name' => 'Rizal'])
        ->assertSessionHasErrors('name');
    $this->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 8, 'name' => 'Rizal'])
        ->assertSessionHasNoErrors();

    expect(Section::count())->toBe(2);
});

it('validates the section form', function () {
    $this->actingAs($this->staff)->post(route('staff.sections.store'), ['school_year_id' => 999, 'grade_level' => 5, 'name' => '', 'capacity' => 500])
        ->assertSessionHasErrors(['school_year_id', 'grade_level', 'name', 'capacity']);
});

it('lists sections per grade with the number of learners without a section', function () {
    $rizal = newSection($this);
    enrolledLearner($this, 7, $rizal);
    enrolledLearner($this, 7);          // no section yet
    enrolledLearner($this, 8);

    $this->actingAs($this->staff)->get(route('staff.sections.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/sections/Index')
            ->where('grades.0.grade', 7)
            ->where('grades.0.enrolled', 2)
            ->where('grades.0.unassigned', 1)
            ->where('grades.0.sections.0.learners', 1)
            ->where('grades.1.enrolled', 1));
});

it('shows the class list and the learners who still wait for a section', function () {
    $rizal = newSection($this);
    enrolledLearner($this, 7, $rizal);
    enrolledLearner($this, 7);
    enrolledLearner($this, 8); // another grade: must not be offered

    $this->actingAs($this->staff)->get(route('staff.sections.show', $rizal))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('members', 1)->has('waiting', 1)->where('section.learners', 1));
});

it('moves learners into a section and out again', function () {
    $rizal = newSection($this);
    $a = enrolledLearner($this);
    $b = enrolledLearner($this);

    $this->actingAs($this->staff)->post(route('staff.sections.assign', $rizal), ['enrollment_ids' => [$a->id, $b->id]])->assertRedirect();
    expect($rizal->enrollments()->count())->toBe(2);

    $this->post(route('staff.sections.unassign', $rizal), ['enrollment_ids' => [$a->id]])->assertRedirect();
    expect($a->fresh()->section_id)->toBeNull()->and($b->fresh()->section_id)->toBe($rizal->id);
});

it('refuses learners of another grade or school year', function () {
    $rizal = newSection($this);
    $wrongGrade = enrolledLearner($this, 8);
    $oldYear = SchoolYear::create(['label' => '2025-2026', 'start_year' => 2025, 'is_current' => false]);
    $wrongYear = Student::factory()->create()->enrollments()->create([
        'school_year_id' => $oldYear->id, 'grade_level' => 7, 'curriculum' => 'k12', 'status' => 'enrolled',
    ]);

    $this->actingAs($this->staff)->post(route('staff.sections.assign', $rizal), ['enrollment_ids' => [$wrongGrade->id]])->assertSessionHasErrors('enrollment_ids');
    $this->post(route('staff.sections.assign', $rizal), ['enrollment_ids' => [$wrongYear->id]])->assertSessionHasErrors('enrollment_ids');

    expect($rizal->enrollments()->count())->toBe(0);
});

it('stops at the capacity of the section', function () {
    $small = newSection($this, ['capacity' => 2]);
    $ids = collect(range(1, 3))->map(fn () => enrolledLearner($this)->id)->all();

    $this->actingAs($this->staff)->post(route('staff.sections.assign', $small), ['enrollment_ids' => $ids])->assertSessionHasErrors('enrollment_ids');
    expect($small->enrollments()->count())->toBe(0);

    $this->post(route('staff.sections.assign', $small), ['enrollment_ids' => array_slice($ids, 0, 2)])->assertSessionHasNoErrors();
    expect($small->seatsLeft())->toBe(0);

    // The section is full now.
    $this->post(route('staff.sections.assign', $small), ['enrollment_ids' => [$ids[2]]])->assertSessionHasErrors('enrollment_ids');
});

it('does not allow a capacity below the learners already inside', function () {
    $rizal = newSection($this, ['capacity' => 10]);
    foreach (range(1, 3) as $_) {
        enrolledLearner($this, 7, $rizal);
    }

    $this->actingAs($this->staff)->put(route('staff.sections.update', $rizal), ['name' => 'Rizal', 'capacity' => 2])->assertSessionHasErrors('capacity');
    $this->put(route('staff.sections.update', $rizal), ['name' => 'Rizal B', 'adviser_name' => 'Mr. Cruz', 'capacity' => 5])->assertSessionHasNoErrors();

    expect($rizal->fresh()->name)->toBe('Rizal B')->and($rizal->fresh()->capacity)->toBe(5);
});

it('does not allow the school year or grade of a section to change', function () {
    $rizal = newSection($this);

    $this->actingAs($this->staff)->put(route('staff.sections.update', $rizal), ['name' => 'Rizal', 'grade_level' => 9])->assertSessionHasErrors('grade_level');
});

it('deletes only an empty section', function () {
    $rizal = newSection($this);
    enrolledLearner($this, 7, $rizal);

    $this->actingAs($this->staff)->delete(route('staff.sections.destroy', $rizal))->assertRedirect();
    expect(Section::count())->toBe(1);

    $rizal->enrollments()->update(['section_id' => null]);
    $this->delete(route('staff.sections.destroy', $rizal))->assertRedirect(route('staff.sections.index', ['school_year_id' => $this->year->id]));
    expect(Section::count())->toBe(0);
});

it('keeps parents out of the sections pages and actions', function () {
    $rizal = newSection($this);
    $a = enrolledLearner($this);
    $parent = parentUser();

    $this->actingAs($parent)->get(route('staff.sections.index'))->assertForbidden();
    $this->post(route('staff.sections.assign', $rizal), ['enrollment_ids' => [$a->id]])->assertForbidden();
    $this->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 7, 'name' => 'X'])->assertForbidden();
    $this->delete(route('staff.sections.destroy', $rizal))->assertForbidden();
});

it('lets the registrar pick a section when approving an online enrollment', function () {
    $rizal = newSection($this, ['capacity' => 40]);
    $parent = parentUser();
    $application = EnrollmentApplication::create([
        'user_id' => $parent->id, 'school_year_id' => $this->year->id, 'type' => 'grade7', 'grade_level' => 7, 'relationship' => 'parent',
        'lrn' => '900000000888', 'last_name' => 'Garcia', 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2014-03-02',
        'address' => 'Cawayan', 'guardian_name' => 'Rosa', 'guardian_contact' => '09171234567',
    ]);

    $this->actingAs($this->staff)->get(route('staff.enrollments.show', $application))
        ->assertInertia(fn ($page) => $page->has('sections', 1)->where('sections.0.label', 'Rizal (0/40)'));

    $this->post(route('staff.enrollments.approve', $application), ['section_id' => $rizal->id])->assertRedirect();

    $enrollment = Student::where('lrn', '900000000888')->first()->enrollments()->first();
    expect($enrollment->section_id)->toBe($rizal->id)->and($enrollment->curriculum)->toBe(Curriculum::K12);
});

it('refuses a section of another grade or a full section when approving', function () {
    $grade8 = newSection($this, ['grade_level' => 8]);
    $full = newSection($this, ['name' => 'Bonifacio', 'capacity' => 1]);
    enrolledLearner($this, 7, $full);
    $application = EnrollmentApplication::create([
        'user_id' => parentUser()->id, 'school_year_id' => $this->year->id, 'type' => 'grade7', 'grade_level' => 7, 'relationship' => 'parent',
        'last_name' => 'Garcia', 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2014-03-02',
        'address' => 'Cawayan', 'guardian_name' => 'Rosa', 'guardian_contact' => '09171234567',
    ]);
    $workflow = app(EnrollmentWorkflow::class);

    expect(fn () => $workflow->approve($application, $this->staff, null, $grade8->id))->toThrow(ValidationException::class)
        ->and(fn () => $workflow->approve($application, $this->staff, null, $full->id))->toThrow(ValidationException::class);
    expect($application->fresh()->status->value)->toBe('submitted');
});

it('follows the K to 12 curriculum from school year 2012 and BEC before', function () {
    // Grade 7 in 2012-2013 was the first K to 12 class; Grade 8 that year was still BEC.
    expect(Curriculum::forLevel(2012, 7))->toBe(Curriculum::K12)
        ->and(Curriculum::forLevel(2012, 8))->toBe(Curriculum::Bec)
        ->and(Curriculum::forLevel(2013, 8))->toBe(Curriculum::K12)
        ->and(Curriculum::forLevel(2005, 7))->toBe(Curriculum::Bec)
        ->and(Curriculum::forLevel(2026, 7))->toBe(Curriculum::K12)
        ->and(Curriculum::forLevel(2026, 12))->toBe(Curriculum::K12)
        ->and(Curriculum::forLevel(2000, 10))->toBe(Curriculum::Bec);
});

it('works out the curriculum when staff leave it empty', function () {
    $student = Student::factory()->create();
    $old = SchoolYear::create(['label' => '2005-2006', 'start_year' => 2005, 'is_current' => false]);

    foreach ([$this->year->id => 'k12', $old->id => 'bec'] as $yearId => $expected) {
        $this->actingAs($this->staff)->post(route('staff.students.enrollments.store', $student), [
            'school_year_id' => $yearId, 'grade_level' => 7, 'status' => 'enrolled',
        ])->assertSessionHasNoErrors();
        expect($student->enrollments()->where('school_year_id', $yearId)->first()->curriculum->value)->toBe($expected);
    }
});
