<?php

use App\Enums\LinkStatus;
use App\Models\Student;
use App\Models\StudentLink;

it('creates a pending link when LRN and birthdate match', function () {
    $student = Student::factory()->create(['lrn' => '900000000001', 'birthdate' => '2010-05-17']);
    $parent = parentUser();

    $this->actingAs($parent)->post(route('portal.link'), [
        'lrn' => '900000000001',
        'birthdate' => '2010-05-17',
        'relationship' => 'parent',
    ])->assertSessionHasNoErrors();

    $link = StudentLink::where('user_id', $parent->id)->firstOrFail();
    expect($link->student_id)->toBe($student->id)
        ->and($link->status)->toBe(LinkStatus::Pending);
});

it('gives the same error for a wrong birthdate and an unknown LRN', function () {
    Student::factory()->create(['lrn' => '900000000002', 'birthdate' => '2010-05-17']);
    $parent = parentUser();

    $wrongDate = $this->actingAs($parent)->post(route('portal.link'), [
        'lrn' => '900000000002', 'birthdate' => '2011-01-01', 'relationship' => 'self',
    ]);
    $unknown = $this->actingAs($parent)->post(route('portal.link'), [
        'lrn' => '900000009999', 'birthdate' => '2010-05-17', 'relationship' => 'self',
    ]);

    $wrongDate->assertSessionHasErrors('lrn');
    $unknown->assertSessionHasErrors('lrn');
    expect(session('errors')->first('lrn'))->toBe(
        $wrongDate->baseResponse->getSession()->get('errors')->first('lrn'),
    );
    expect(StudentLink::count())->toBe(0);
});

it('lets staff approve a request and the parent then sees the record', function () {
    $student = Student::factory()->create();
    $parent = parentUser();
    $link = StudentLink::create([
        'user_id' => $parent->id, 'student_id' => $student->id,
        'relationship' => 'parent', 'status' => LinkStatus::Pending,
    ]);

    $staff = staffUser();
    $this->actingAs($staff)->post(route('staff.links.approve', $link))->assertRedirect();

    expect($link->fresh()->status)->toBe(LinkStatus::Approved)
        ->and($link->fresh()->reviewed_by)->toBe($staff->id);
    $this->actingAs($parent)->get(route('portal.records.show', $student))->assertOk();
});

it('does not let a parent approve their own request', function () {
    $student = Student::factory()->create();
    $parent = parentUser();
    $link = StudentLink::create([
        'user_id' => $parent->id, 'student_id' => $student->id,
        'relationship' => 'parent', 'status' => LinkStatus::Pending,
    ]);

    $this->actingAs($parent)->post(route('staff.links.approve', $link))->assertForbidden();
    expect($link->fresh()->status)->toBe(LinkStatus::Pending);
});
