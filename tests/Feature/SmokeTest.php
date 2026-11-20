<?php

use App\Models\DocumentRequest;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Student;
use Database\Seeders\FaqSeeder;

/*
| Smoke test: every page opens without an error for the role that should see it,
| and is closed for the roles that should not. The page files must also exist in
| the built assets (run `npm run build` first), so a missing Vue page fails here.
*/

beforeEach(function () {
    SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    $this->seed(FaqSeeder::class);
});

it('opens every public page', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['home', 'help', 'login', 'register']);

it('opens every page for a parent', function () {
    $student = Student::factory()->create();
    $parent = parentUser(linkedTo: $student);
    $request = DocumentRequest::create(['user_id' => $parent->id, 'student_id' => $student->id, 'document_type' => 'form_137', 'purpose' => 'x', 'copies' => 1]);
    $this->actingAs($parent);

    foreach ([
        route('dashboard'), route('portal.records'), route('portal.records.show', $student),
        route('portal.requests.index'), route('portal.requests.create'), route('portal.requests.show', $request),
        route('portal.enrollments.index'), route('portal.enrollments.create'),
        route('profile.edit'), route('appearance.edit'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('opens every staff page for staff', function () {
    $student = Student::factory()->create();
    $parent = parentUser(linkedTo: $student);
    $request = DocumentRequest::create(['user_id' => $parent->id, 'student_id' => $student->id, 'document_type' => 'form_137', 'purpose' => 'x', 'copies' => 1]);
    $application = EnrollmentApplication::create([
        'user_id' => $parent->id, 'school_year_id' => SchoolYear::first()->id, 'type' => 'grade7', 'grade_level' => 7, 'relationship' => 'parent',
        'last_name' => 'Garcia', 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2014-01-01', 'address' => 'Cawayan',
        'guardian_name' => 'Rosa', 'guardian_contact' => '09171234567',
    ]);
    $this->actingAs(staffUser());

    foreach ([
        route('dashboard'),
        route('staff.students.index'), route('staff.students.create'), route('staff.students.show', $student), route('staff.students.edit', $student),
        route('staff.requests.index'), route('staff.requests.show', $request),
        route('staff.enrollments.index'), route('staff.enrollments.show', $application),
        route('staff.ocr.index'), route('staff.ocr.create'),
        route('staff.alerts.index'), route('staff.links.index'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('opens every admin page for the principal', function () {
    $this->actingAs(adminUser());

    foreach ([route('dashboard'), route('admin.audit-log'), route('admin.surveys'), route('staff.students.index'), route('staff.alerts.index')] as $url) {
        $this->get($url)->assertOk();
    }
});

it('keeps parents out of every staff and admin page', function (string $route) {
    $this->actingAs(parentUser())->get(route($route))->assertForbidden();
})->with([
    'staff.students.index', 'staff.requests.index', 'staff.enrollments.index', 'staff.ocr.index', 'staff.ocr.create',
    'staff.alerts.index', 'staff.links.index', 'admin.audit-log', 'admin.surveys', 'admin.dashboard.export',
]);

it('keeps staff out of the principal-only pages', function (string $route) {
    $this->actingAs(staffUser())->get(route($route))->assertForbidden();
})->with(['admin.audit-log', 'admin.surveys', 'admin.dashboard.export']);

it('sends guests to the login page from every private page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'dashboard', 'portal.records', 'portal.requests.index', 'portal.enrollments.index', 'staff.students.index',
    'staff.requests.index', 'staff.enrollments.index', 'staff.ocr.index', 'staff.alerts.index', 'admin.audit-log', 'admin.surveys',
]);
