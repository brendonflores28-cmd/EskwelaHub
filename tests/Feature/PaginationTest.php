<?php

use App\Enums\LinkStatus;
use App\Models\DuplicateFlag;
use App\Models\EnrollmentApplication;
use App\Models\OcrDraft;
use App\Models\ScanUpload;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentLink;
use App\Models\User;

/*
| Every long list is paged. These tests check the first and the second page.
*/

it('pages the account link approvals (10 per page)', function () {
    $student = Student::factory()->create();
    foreach (range(1, 12) as $i) {
        StudentLink::create(['user_id' => User::factory()->create()->id, 'student_id' => $student->id, 'relationship' => 'parent', 'status' => LinkStatus::Pending]);
    }
    $this->actingAs(staffUser());

    $this->get(route('staff.links.index'))
        ->assertInertia(fn ($page) => $page->has('links.data', 10)->where('links.total', 12)->where('links.last_page', 2));
    $this->get(route('staff.links.index', ['page' => 2]))
        ->assertInertia(fn ($page) => $page->has('links.data', 2));
});

it('pages the scanned-record drafts (15 per page)', function () {
    $staff = staffUser();
    foreach (range(1, 17) as $i) {
        $scan = ScanUpload::create(['uploaded_by' => $staff->id, 'original_name' => "s{$i}.png", 'disk_path' => "scans/s{$i}.png", 'mime' => 'image/png', 'size' => 1, 'engine' => 'browser']);
        OcrDraft::create(['scan_upload_id' => $scan->id, 'status' => 'pending']);
    }

    $this->actingAs($staff)->get(route('staff.ocr.index'))
        ->assertInertia(fn ($page) => $page->has('drafts.data', 15)->where('drafts.last_page', 2));
});

it('pages the enrollment forms of a parent (10 per page)', function () {
    $parent = parentUser();
    $year = SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    foreach (range(1, 11) as $i) {
        EnrollmentApplication::create([
            'user_id' => $parent->id, 'school_year_id' => $year->id, 'type' => 'grade7', 'grade_level' => 7, 'relationship' => 'parent',
            'last_name' => "Learner{$i}", 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2014-01-01', 'address' => 'Cawayan',
            'guardian_name' => 'Rosa', 'guardian_contact' => '09171234567',
        ]);
    }

    $this->actingAs($parent)->get(route('portal.enrollments.index'))
        ->assertInertia(fn ($page) => $page->has('applications.data', 10)->where('applications.meta.last_page', 2));
});

it('pages the possible duplicate pairs (8 per page) apart from the incomplete-records list', function () {
    foreach (range(1, 9) as $i) {
        $a = Student::factory()->create();
        $b = Student::factory()->create();
        DuplicateFlag::create(['student_a_id' => min($a->id, $b->id), 'student_b_id' => max($a->id, $b->id), 'score' => 0.9, 'reason' => 'test', 'status' => 'open']);
    }
    $this->actingAs(staffUser());

    $this->get(route('staff.alerts.index'))
        ->assertInertia(fn ($page) => $page->has('duplicates.data', 8)->where('duplicates.total', 9));
    $this->get(route('staff.alerts.index', ['dup_page' => 2]))
        ->assertInertia(fn ($page) => $page->has('duplicates.data', 1));
});

it('pages the audit trail and the surveys', function () {
    $admin = adminUser();
    foreach (range(1, 30) as $i) {
        activity('access')->causedBy($admin)->event('viewed')->log("View {$i}");
    }

    $this->actingAs($admin)->get(route('admin.audit-log'))
        ->assertInertia(fn ($page) => $page->has('entries.data', 25)->where('entries.last_page', 2));
    $this->get(route('admin.surveys'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('surveys.last_page', 1));
});
