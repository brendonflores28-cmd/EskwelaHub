<?php

use App\Enums\LinkStatus;
use App\Enums\StudentStatus;
use App\Models\DocumentRequest;
use App\Models\DuplicateFlag;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentLink;
use App\Services\Duplicates\CompletenessChecker;
use App\Services\Duplicates\DuplicateDetector;
use App\Services\Duplicates\StudentMerger;
use Spatie\Activitylog\Models\Activity;

function twin(array $overrides = []): Student
{
    return Student::factory()->create(array_merge([
        // Middle name and suffix are fixed: the factory picks random ones that would change the name match.
        'last_name' => 'Marasigan', 'first_name' => 'Arnel', 'middle_name' => null, 'suffix' => null, 'birthdate' => '2008-04-12',
    ], $overrides));
}

it('flags two records with the same birthdate and a very similar name', function () {
    $a = twin();
    $b = twin(['last_name' => 'Marasigan', 'first_name' => 'Arnell']); // typo

    expect(app(DuplicateDetector::class)->scan())->toBe(1);

    $flag = DuplicateFlag::firstOrFail();
    expect([$flag->student_a_id, $flag->student_b_id])->toBe([$a->id, $b->id])
        ->and((float) $flag->score)->toBeGreaterThanOrEqual(0.85)
        ->and($flag->status)->toBe('open');
});

it('flags a record that only lacks the middle name', function () {
    twin(['middle_name' => 'Lopez']);
    twin(['first_name' => 'Arnel', 'middle_name' => null]);

    expect(app(DuplicateDetector::class)->scan())->toBe(1);
});

it('does not flag different birthdates or different names', function () {
    twin();
    twin(['birthdate' => '2009-04-12']);                                   // same name, other birthdate
    twin(['last_name' => 'Villanueva', 'first_name' => 'Carlo']);          // same birthdate, other person

    expect(app(DuplicateDetector::class)->scan())->toBe(0)
        ->and(DuplicateFlag::count())->toBe(0);
});

it('is safe to run again and never reopens a dismissed pair', function () {
    twin();
    twin(['first_name' => 'Arnell']);
    $detector = app(DuplicateDetector::class);

    expect($detector->scan())->toBe(1)->and($detector->scan())->toBe(0);

    DuplicateFlag::first()->update(['status' => 'dismissed']);
    $detector->scan();
    expect(DuplicateFlag::first()->status)->toBe('dismissed')->and(DuplicateFlag::count())->toBe(1);
});

it('finds look-alikes of a person who is about to be added', function () {
    twin();

    $found = app(DuplicateDetector::class)->similarTo('Marasigan', 'Arnel', '2008-04-12');

    expect($found)->toHaveCount(1)
        ->and(app(DuplicateDetector::class)->similarTo('Reyes', 'Maria', '2008-04-12'))->toHaveCount(0)
        ->and(app(DuplicateDetector::class)->similarTo(null, 'Arnel', '2008-04-12'))->toHaveCount(0);
});

it('merges two records without losing history', function () {
    $y1 = SchoolYear::create(['label' => '2020-2021', 'start_year' => 2020, 'is_current' => false]);
    $y2 = SchoolYear::create(['label' => '2021-2022', 'start_year' => 2021, 'is_current' => false]);
    $keep = twin(['lrn' => null, 'birthplace' => null]);
    $remove = twin(['first_name' => 'Arnell', 'lrn' => '900000000321', 'birthplace' => 'Bongabong']);
    $keep->enrollments()->create(['school_year_id' => $y1->id, 'grade_level' => 7, 'curriculum' => 'k12', 'status' => 'enrolled']);
    $remove->enrollments()->create(['school_year_id' => $y1->id, 'grade_level' => 8, 'curriculum' => 'k12', 'status' => 'enrolled']); // same year: kept record wins
    $remove->enrollments()->create(['school_year_id' => $y2->id, 'grade_level' => 8, 'curriculum' => 'k12', 'status' => 'enrolled']);
    $parent = parentUser();
    StudentLink::create(['user_id' => $parent->id, 'student_id' => $remove->id, 'relationship' => 'parent', 'status' => LinkStatus::Approved]);
    $request = DocumentRequest::create(['user_id' => $parent->id, 'student_id' => $remove->id, 'document_type' => 'form_137', 'purpose' => 'x', 'copies' => 1]);
    app(DuplicateDetector::class)->scan();
    $staff = staffUser();

    app(StudentMerger::class)->merge($keep, $remove, $staff, DuplicateFlag::first());

    $keep->refresh();
    expect($remove->fresh()->trashed())->toBeTrue()
        ->and($keep->enrollments()->count())->toBe(2)
        ->and($keep->enrollments()->where('school_year_id', $y1->id)->value('grade_level'))->toBe(7)
        ->and($keep->lrn)->toBe('900000000321')               // gap filled from the other record
        ->and($keep->birthplace)->toBe('Bongabong')
        ->and($request->fresh()->student_id)->toBe($keep->id)
        ->and($parent->canAccessStudent($keep))->toBeTrue()
        ->and(DuplicateFlag::first()->status)->toBe('merged')
        ->and(Activity::where('event', 'merged')->count())->toBe(1);
});

it('refuses to merge a record into itself', function () {
    $s = twin();

    expect(fn () => app(StudentMerger::class)->merge($s, $s, staffUser()))->toThrow(InvalidArgumentException::class);
});

it('lets staff review alerts and blocks everyone else', function () {
    $keep = twin();
    $remove = twin(['first_name' => 'Arnell']);
    app(DuplicateDetector::class)->scan();
    $flag = DuplicateFlag::firstOrFail();

    $this->actingAs(parentUser())->get(route('staff.alerts.index'))->assertForbidden();
    $this->actingAs(parentUser())->post(route('staff.alerts.merge', $flag), ['keep' => $keep->id])->assertForbidden();

    $this->actingAs(staffUser())->get(route('staff.alerts.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('staff/alerts/Index')->has('duplicates.data', 1));

    // Merging must keep one of the two records of the flag.
    $this->post(route('staff.alerts.merge', $flag), ['keep' => 999999])->assertStatus(422);
    $this->post(route('staff.alerts.merge', $flag), ['keep' => $keep->id])->assertRedirect(route('staff.students.show', $keep));
    expect($remove->fresh()->trashed())->toBeTrue();
});

it('lets staff dismiss a pair', function () {
    twin();
    twin(['first_name' => 'Arnell']);
    app(DuplicateDetector::class)->scan();
    $flag = DuplicateFlag::firstOrFail();

    $this->actingAs(staffUser())->post(route('staff.alerts.dismiss', $flag))->assertRedirect();

    expect($flag->fresh()->status)->toBe('dismissed');
});

it('counts incomplete records', function () {
    $year = SchoolYear::create(['label' => '2015-2016', 'start_year' => 2015, 'is_current' => false]);
    $noLrn = Student::factory()->create(['lrn' => null]);
    $noLrn->enrollments()->create(['school_year_id' => $year->id, 'grade_level' => 7, 'curriculum' => 'k12', 'status' => 'enrolled']);
    Student::factory()->create(['status' => StudentStatus::Graduated]);                       // graduated, nothing else
    $grad = Student::factory()->create(['status' => StudentStatus::Graduated]);
    $grad->graduation()->create(['school_year_id' => $year->id]);                              // graduation without diploma no.
    Student::factory()->create(['birthplace' => null]);

    $counts = app(CompletenessChecker::class)->counts();

    expect($counts['missing_lrn'])->toBe(1)
        ->and($counts['graduated_without_record'])->toBe(1)
        ->and($counts['graduation_without_diploma'])->toBe(1)
        ->and($counts['missing_birthplace'])->toBe(1)
        ->and($counts['no_enrollment'])->toBe(3);

    $this->actingAs(staffUser())->get(route('staff.alerts.index', ['issue' => 'missing_lrn']))
        ->assertInertia(fn ($page) => $page->has('incomplete.data', 1));
});

it('runs from the artisan command', function () {
    twin();
    twin(['first_name' => 'Arnell']);

    $this->artisan('eskwelahub:detect-duplicates')->expectsOutputToContain('1 new')->assertSuccessful();
});
