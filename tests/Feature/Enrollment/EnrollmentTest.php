<?php

use App\Enums\EnrollmentStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Notifications\EnrollmentDecided;
use App\Services\Enrollment\EnrollmentWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('private');
    $this->year = SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    $this->parent = parentUser();
    $this->staff = staffUser();
});

function enrollmentForm(array $overrides = []): array
{
    return array_merge([
        'type' => 'grade7',
        'relationship' => 'parent',
        'last_name' => 'Garcia',
        'first_name' => 'Ana',
        'sex' => 'F',
        'birthdate' => '2014-03-02',
        'address' => 'Brgy. Cawayan, Bongabong',
        'guardian_name' => 'Rosa Garcia',
        'guardian_contact' => '0917 123 4567',
        'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 200, 'application/pdf'),
        'report_card' => UploadedFile::fake()->image('card.jpg'),
    ], $overrides);
}

it('accepts a Grade 7 form with documents and stores the files privately', function () {
    Notification::fake();

    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm())->assertRedirect();

    $application = EnrollmentApplication::firstOrFail();
    expect($application->status)->toBe(EnrollmentStatus::Submitted)
        ->and($application->reference_no)->toMatch('/^ENR-\d{4}-\d{6}$/')
        ->and($application->grade_level)->toBe(7)
        ->and($application->documents)->toHaveCount(2);
    foreach ($application->documents as $document) {
        Storage::disk('private')->assertExists($document->disk_path);
        expect($document->disk_path)->toStartWith('enrollments/'.$application->id.'/');
    }
    Notification::assertSentTo($this->parent, EnrollmentDecided::class);
});

it('requires previous school and good moral certificate from transferees', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm(['type' => 'transferee']))
        ->assertSessionHasErrors(['grade_level', 'previous_school', 'good_moral']);

    $this->post(route('portal.enrollments.store'), enrollmentForm([
        'type' => 'transferee', 'grade_level' => 9, 'previous_school' => 'Roxas NHS',
        'good_moral' => UploadedFile::fake()->create('gm.pdf', 50, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    expect(EnrollmentApplication::first()->grade_level)->toBe(9);
});

it('rejects wrong file types, big files and missing documents', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm([
        'psa_birth_certificate' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        'report_card' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
    ]))->assertSessionHasErrors(['psa_birth_certificate', 'report_card']);

    $this->post(route('portal.enrollments.store'), array_diff_key(enrollmentForm(), ['report_card' => 1]))
        ->assertSessionHasErrors('report_card');

    expect(EnrollmentApplication::count())->toBe(0);
});

it('blocks a second open form for the same learner', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm());
    $this->post(route('portal.enrollments.store'), enrollmentForm())->assertSessionHasErrors('last_name');

    expect(EnrollmentApplication::count())->toBe(1);
});

it('approves: creates the student, enrolls them and links the parent', function () {
    Notification::fake();
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm(['lrn' => '900000000555']));
    $application = EnrollmentApplication::firstOrFail();

    $this->actingAs($this->staff)->post(route('staff.enrollments.approve', $application), ['note' => 'Welcome'])->assertRedirect();

    $application->refresh();
    expect($application->status)->toBe(EnrollmentStatus::Approved);
    $student = Student::where('lrn', '900000000555')->firstOrFail();
    expect($application->student_id)->toBe($student->id)
        ->and($student->enrollments()->where('school_year_id', $this->year->id)->value('grade_level'))->toBe(7);
    // The parent can now see the record and ask for documents.
    $this->actingAs($this->parent)->get(route('portal.records.show', $student))->assertOk();
    Notification::assertSentTo($this->parent, EnrollmentDecided::class, fn ($n) => $n->status === EnrollmentStatus::Approved);
});

it('reuses an existing student with the same LRN instead of duplicating', function () {
    $existing = Student::factory()->create(['lrn' => '900000000777']);
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm(['lrn' => '900000000777']));

    $this->actingAs($this->staff)->post(route('staff.enrollments.approve', EnrollmentApplication::first()));

    expect(Student::where('lrn', '900000000777')->count())->toBe(1)
        ->and(EnrollmentApplication::first()->student_id)->toBe($existing->id);
});

it('rejects only with a reason and a decided form cannot change again', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm());
    $application = EnrollmentApplication::firstOrFail();

    $this->actingAs($this->staff)->post(route('staff.enrollments.reject', $application), [])->assertSessionHasErrors('note');
    $this->post(route('staff.enrollments.reject', $application), ['note' => 'Birth certificate is blurry'])->assertRedirect();
    expect($application->fresh()->status)->toBe(EnrollmentStatus::Rejected);

    $this->post(route('staff.enrollments.approve', $application))->assertRedirect();
    expect($application->fresh()->status)->toBe(EnrollmentStatus::Rejected)
        ->and(Student::count())->toBe(0);
});

it('lets only the owner and staff see a form and its documents', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm());
    $application = EnrollmentApplication::firstOrFail();
    $document = $application->documents->first();

    $this->actingAs($this->parent)->get(route('portal.enrollments.show', $application))->assertOk();
    $this->actingAs($this->staff)->get(route('staff.enrollments.show', $application))->assertOk();

    $stranger = parentUser();
    $this->actingAs($stranger)->get(route('portal.enrollments.show', $application))->assertForbidden();
    $this->actingAs($stranger)->get(route('enrollment-documents.request', $document))->assertForbidden();
    $this->actingAs($stranger)->get(route('staff.enrollments.index'))->assertForbidden();
    $this->actingAs($this->parent)->post(route('staff.enrollments.approve', $application))->assertForbidden();
});

it('serves documents only through a signed link and logs each open', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm());
    $document = EnrollmentApplication::firstOrFail()->documents->first();

    // A plain URL does not work.
    $this->actingAs($this->staff)->get(route('enrollment-documents.file', $document))->assertForbidden();

    $location = $this->actingAs($this->staff)->get(route('enrollment-documents.request', $document))->headers->get('Location');
    $this->actingAs($this->staff)->get($location)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    expect(Activity::where('event', 'document_opened')->count())->toBe(1);
});

it('does not let two clicks approve twice', function () {
    $this->actingAs($this->parent)->post(route('portal.enrollments.store'), enrollmentForm());
    $application = EnrollmentApplication::firstOrFail();
    $workflow = app(EnrollmentWorkflow::class);

    $workflow->approve($application, $this->staff);

    expect(fn () => $workflow->approve($application->fresh(), $this->staff))
        ->toThrow(InvalidRequestTransition::class);
    expect(Student::count())->toBe(1);
});
