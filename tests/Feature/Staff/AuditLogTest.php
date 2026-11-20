<?php

use App\Models\Student;
use Spatie\Activitylog\Models\Activity;

it('logs who viewed a student record', function () {
    $student = Student::factory()->create();
    $staff = staffUser();

    $this->actingAs($staff)->get(route('staff.students.show', $student))->assertOk();

    $entry = Activity::where('log_name', 'access')->where('event', 'viewed')->firstOrFail();
    expect($entry->causer_id)->toBe($staff->id)
        ->and($entry->subject_id)->toBe($student->id)
        ->and($entry->properties['ip'])->not->toBeNull();
});

it('logs when a parent views a linked record', function () {
    $student = Student::factory()->create();
    $parent = parentUser(linkedTo: $student);

    $this->actingAs($parent)->get(route('portal.records.show', $student))->assertOk();

    expect(Activity::where('event', 'viewed')->where('causer_id', $parent->id)->count())->toBe(1);
});

it('does not log a view when access is denied', function () {
    $student = Student::factory()->create();

    $this->actingAs(parentUser())->get(route('portal.records.show', $student))->assertForbidden();

    expect(Activity::where('event', 'viewed')->count())->toBe(0);
});

it('logs changes to a student record', function () {
    $student = Student::factory()->create(['last_name' => 'Reyes']);

    $this->actingAs(staffUser())->put(route('staff.students.update', $student), [
        'last_name' => 'Reyes-Santos',
        'first_name' => $student->first_name,
        'sex' => $student->sex,
        'birthdate' => $student->birthdate->toDateString(),
        'status' => 'enrolled',
    ])->assertRedirect();

    $entry = Activity::where('log_name', 'students')->where('event', 'updated')->firstOrFail();
    expect($entry->properties['attributes']['last_name'])->toBe('Reyes-Santos');
});
