<?php

use App\Enums\LinkStatus;
use App\Models\Student;
use App\Models\StudentLink;
use App\Models\User;

/*
| Access rules for student records (RA 10173: records of minors).
| Students and parents see only their own linked records.
*/

it('lets staff open any student record', function () {
    $student = Student::factory()->create();

    $this->actingAs(staffUser())
        ->get(route('staff.students.show', $student))
        ->assertOk();
});

it('lets a parent open only a record that staff approved', function () {
    $own = Student::factory()->create();
    $other = Student::factory()->create();
    $parent = parentUser(linkedTo: $own);

    $this->actingAs($parent)->get(route('portal.records.show', $own))->assertOk();
    $this->actingAs($parent)->get(route('portal.records.show', $other))->assertForbidden();
});

it('does not give access while a link is still pending or was rejected', function (LinkStatus $status) {
    $student = Student::factory()->create();
    $parent = parentUser();
    StudentLink::create([
        'user_id' => $parent->id,
        'student_id' => $student->id,
        'relationship' => 'parent',
        'status' => $status,
    ]);

    $this->actingAs($parent)->get(route('portal.records.show', $student))->assertForbidden();
})->with([LinkStatus::Pending, LinkStatus::Rejected]);

it('keeps parents out of the staff portal', function () {
    $student = Student::factory()->create();
    $parent = parentUser(linkedTo: $student);

    $this->actingAs($parent)->get(route('staff.students.index'))->assertForbidden();
    $this->actingAs($parent)->get(route('staff.students.show', $student))->assertForbidden();
    $this->actingAs($parent)->get(route('staff.links.index'))->assertForbidden();
});

it('keeps staff out of the admin audit log', function () {
    $this->actingAs(staffUser())->get(route('admin.audit-log'))->assertForbidden();
    $this->actingAs(adminUser())->get(route('admin.audit-log'))->assertOk();
});

it('lets only admin archive a student record', function () {
    $student = Student::factory()->create();

    $this->actingAs(staffUser())->delete(route('staff.students.destroy', $student))->assertForbidden();
    expect($student->fresh()->trashed())->toBeFalse();

    $this->actingAs(adminUser())->delete(route('staff.students.destroy', $student))->assertRedirect();
    expect($student->fresh()->trashed())->toBeTrue();
});

it('sends guests to the login page', function () {
    $student = Student::factory()->create();

    $this->get(route('staff.students.show', $student))->assertRedirect(route('login'));
    $this->get(route('portal.records'))->assertRedirect(route('login'));
});

it('blocks staff without two-factor authentication from the staff portal', function () {
    $staff = User::factory()->create(); // no 2FA
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('staff.students.index'))
        ->assertRedirect(route('security.edit'));
});

it('lets staff in when the 2FA requirement is switched off', function () {
    config(['eskwelahub.require_staff_2fa' => false]);
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('staff.students.index'))->assertOk();
});

it('gives public registrations the student/parent role only', function () {
    $this->post(route('register.store'), [
        'name' => 'Test Parent',
        'email' => 'new-parent@example.test',
        'password' => 'a-long-Password-123!',
        'password_confirmation' => 'a-long-Password-123!',
    ]);

    $user = User::where('email', 'new-parent@example.test')->firstOrFail();
    expect($user->getRoleNames()->all())->toBe(['student_parent'])
        ->and($user->can('students.view'))->toBeFalse();
});
