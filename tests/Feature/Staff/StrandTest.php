<?php

use App\Enums\Strand;
use App\Exports\DashboardExport;
use App\Models\EnrollmentApplication;
use App\Models\OcrDraft;
use App\Models\ScanUpload;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Enrollment\EnrollmentWorkflow;
use App\Services\Reports\DashboardMetrics;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| Senior high (Grades 11 and 12): strand of the learner's enrollment and of the class section.
*/

beforeEach(function () {
    $this->year = SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    $this->staff = staffUser();
});

function shLearner($test, int $grade = 11, ?Strand $strand = null): StudentEnrollment
{
    return Student::factory()->create(['middle_name' => null, 'suffix' => null])->enrollments()->create([
        'school_year_id' => $test->year->id, 'grade_level' => $grade, 'strand' => $strand, 'curriculum' => 'k12', 'status' => 'enrolled',
    ]);
}

function shSection($test, array $overrides = []): Section
{
    return Section::create(array_merge([
        'school_year_id' => $test->year->id, 'grade_level' => 11, 'strand' => Strand::Stem, 'name' => 'STEM-A',
    ], $overrides));
}

it('knows which grades are senior high', function () {
    expect(Strand::appliesTo(10))->toBeFalse()
        ->and(Strand::appliesTo(11))->toBeTrue()
        ->and(Strand::appliesTo(12))->toBeTrue();
});

it('offers every strand unless the school lists the ones it has', function () {
    config(['eskwelahub.strands' => '']);
    expect(Strand::offered())->toHaveCount(count(Strand::cases()));

    config(['eskwelahub.strands' => 'stem, abm ,humss,nonsense']);
    expect(Strand::offeredValues())->toBe(['STEM', 'ABM', 'HUMSS']);

    config(['eskwelahub.strands' => 'nonsense']);
    expect(Strand::offered())->toHaveCount(count(Strand::cases())); // nothing valid: fall back to all
});

it('requires a strand for Grade 11 and 12 and forbids one below', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->staff)->post(route('staff.students.enrollments.store', $student), [
        'school_year_id' => $this->year->id, 'grade_level' => 11, 'status' => 'enrolled',
    ])->assertSessionHasErrors('strand');

    $this->post(route('staff.students.enrollments.store', $student), [
        'school_year_id' => $this->year->id, 'grade_level' => 8, 'strand' => 'STEM', 'status' => 'enrolled',
    ])->assertSessionHasErrors('strand');

    $this->post(route('staff.students.enrollments.store', $student), [
        'school_year_id' => $this->year->id, 'grade_level' => 11, 'strand' => 'MAGIC', 'status' => 'enrolled',
    ])->assertSessionHasErrors('strand');

    $this->post(route('staff.students.enrollments.store', $student), [
        'school_year_id' => $this->year->id, 'grade_level' => 11, 'strand' => 'STEM', 'status' => 'enrolled',
    ])->assertSessionHasNoErrors();

    expect($student->enrollments()->first()->strand)->toBe(Strand::Stem);
});

it('only accepts strands the school offers', function () {
    config(['eskwelahub.strands' => 'STEM,ABM']);
    $student = Student::factory()->create();

    $this->actingAs($this->staff)->post(route('staff.students.enrollments.store', $student), [
        'school_year_id' => $this->year->id, 'grade_level' => 12, 'strand' => 'HUMSS', 'status' => 'enrolled',
    ])->assertSessionHasErrors('strand');
});

it('creates senior high sections with a strand and junior high sections without', function () {
    $this->actingAs($this->staff)->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 11, 'name' => 'A'])
        ->assertSessionHasErrors('strand');
    $this->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 7, 'name' => 'Rizal', 'strand' => 'STEM'])
        ->assertSessionHasErrors('strand');
    $this->post(route('staff.sections.store'), ['school_year_id' => $this->year->id, 'grade_level' => 11, 'strand' => 'ABM', 'name' => 'ABM-A'])
        ->assertSessionHasNoErrors();

    $section = Section::firstOrFail();
    expect($section->strand)->toBe(Strand::Abm)->and($section->display_name)->toBe('Grade 11 ABM - ABM-A');
});

it('keeps the strand when a section is edited, and blocks a change while it has learners', function () {
    $stem = shSection($this);

    $this->actingAs($this->staff)->put(route('staff.sections.update', $stem), ['name' => 'STEM-Alpha', 'adviser_name' => 'Ms. Cruz'])
        ->assertSessionHasNoErrors();
    expect($stem->fresh()->strand)->toBe(Strand::Stem);

    shLearner($this, 11, Strand::Stem)->update(['section_id' => $stem->id]);
    $this->put(route('staff.sections.update', $stem), ['name' => 'STEM-Alpha', 'strand' => 'ABM'])->assertSessionHasErrors('strand');
    expect($stem->fresh()->strand)->toBe(Strand::Stem);
});

it('puts only learners of the same strand into a section', function () {
    $stem = shSection($this);
    $stemLearner = shLearner($this, 11, Strand::Stem);
    $abmLearner = shLearner($this, 11, Strand::Abm);
    $undecided = shLearner($this, 11);

    // The waiting list offers STEM and undecided learners, not ABM.
    $this->actingAs($this->staff)->get(route('staff.sections.show', $stem))
        ->assertInertia(fn ($page) => $page->has('waiting', 2));

    $this->post(route('staff.sections.assign', $stem), ['enrollment_ids' => [$abmLearner->id]])->assertSessionHasErrors('enrollment_ids');
    expect($abmLearner->fresh()->section_id)->toBeNull();

    $this->post(route('staff.sections.assign', $stem), ['enrollment_ids' => [$stemLearner->id, $undecided->id]])->assertSessionHasNoErrors();
    // A learner with no strand yet takes the strand of the section.
    expect($undecided->fresh()->strand)->toBe(Strand::Stem)->and($stem->enrollments()->count())->toBe(2);
});

function onlineForm(array $overrides = []): array
{
    return array_merge([
        'type' => 'transferee', 'grade_level' => 11, 'strand' => 'STEM', 'previous_school' => 'Roxas NHS', 'relationship' => 'parent',
        'last_name' => 'Garcia', 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2009-03-02', 'address' => 'Cawayan, Bongabong',
        'guardian_name' => 'Rosa Garcia', 'guardian_contact' => '0917 123 4567',
        'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 100, 'application/pdf'),
        'report_card' => UploadedFile::fake()->image('card.jpg'),
        'good_moral' => UploadedFile::fake()->create('gm.pdf', 50, 'application/pdf'),
    ], $overrides);
}

it('asks a Grade 11 or 12 transferee for a strand and ignores it for everyone else', function () {
    Storage::fake('private');
    $parent = parentUser();

    $this->actingAs($parent)->post(route('portal.enrollments.store'), onlineForm(['strand' => '']))->assertSessionHasErrors('strand');
    $this->post(route('portal.enrollments.store'), onlineForm())->assertSessionHasNoErrors();
    expect(EnrollmentApplication::first()->strand)->toBe(Strand::Stem);

    // A new Grade 7 learner has no strand, even if one is sent.
    $this->post(route('portal.enrollments.store'), onlineForm([
        'type' => 'grade7', 'strand' => 'ABM', 'first_name' => 'Ben', 'last_name' => 'Reyes', 'good_moral' => null, 'grade_level' => null,
    ]))->assertSessionHasNoErrors();
    expect(EnrollmentApplication::where('first_name', 'Ben')->first()->strand)->toBeNull();
});

it('carries the strand into the enrollment and refuses a section of another strand', function () {
    Storage::fake('private');
    $parent = parentUser();
    $this->actingAs($parent)->post(route('portal.enrollments.store'), onlineForm(['lrn' => '900000000411']));
    $application = EnrollmentApplication::firstOrFail();
    $abm = shSection($this, ['strand' => Strand::Abm, 'name' => 'ABM-A']);
    $stem = shSection($this);
    $workflow = app(EnrollmentWorkflow::class);

    // The approval screen only offers sections of the learner's strand.
    $this->actingAs($this->staff)->get(route('staff.enrollments.show', $application))
        ->assertInertia(fn ($page) => $page->has('sections', 1)->where('sections.0.id', $stem->id));

    expect(fn () => $workflow->approve($application, $this->staff, null, $abm->id))->toThrow(ValidationException::class);

    $workflow->approve($application->fresh(), $this->staff, null, $stem->id);

    $enrollment = Student::where('lrn', '900000000411')->first()->enrollments()->first();
    expect($enrollment->strand)->toBe(Strand::Stem)->and($enrollment->section_id)->toBe($stem->id)->and($enrollment->grade_level)->toBe(11);
});

it('stores the strand when a scanned Grade 12 record is verified', function () {
    Storage::fake('private');
    $year = SchoolYear::create(['label' => '2020-2021', 'start_year' => 2020, 'is_current' => false]);
    $scan = ScanUpload::create(['uploaded_by' => $this->staff->id, 'original_name' => 's.png', 'disk_path' => 'scans/s.png', 'mime' => 'image/png', 'size' => 1, 'engine' => 'browser']);
    $draft = OcrDraft::create(['scan_upload_id' => $scan->id, 'status' => 'pending']);

    $this->actingAs($this->staff)->post(route('staff.ocr.verify', $draft), [
        'last_name' => 'Santos', 'first_name' => 'Juan', 'sex' => 'M', 'birthdate' => '2003-01-05',
        'school_year_id' => $year->id, 'grade_level' => 12, 'strand' => 'HUMSS',
    ])->assertRedirect();

    expect(Student::first()->enrollments()->first()->strand)->toBe(Strand::Humss);
});

it('counts senior high learners per strand for the dashboard and the Excel file', function () {
    foreach ([[11, Strand::Stem], [11, Strand::Stem], [12, Strand::Stem], [11, Strand::Abm]] as [$grade, $strand]) {
        shLearner($this, $grade, $strand);
    }
    shLearner($this, 9); // junior high: not counted

    $metrics = app(DashboardMetrics::class)->all();

    expect($metrics['strands'])->toBe([
        ['strand' => 'STEM', 'label' => 'STEM', 'grade11' => 2, 'grade12' => 1, 'total' => 3],
        ['strand' => 'ABM', 'label' => 'ABM', 'grade11' => 1, 'grade12' => 0, 'total' => 1],
    ]);

    $sheets = collect((new DashboardExport($metrics))->sheets());
    expect($sheets->map->title()->all())->toContain('Senior high')
        ->and($sheets->first(fn ($s) => $s->title() === 'Senior high')->array())->toBe([['STEM', 2, 1, 3], ['ABM', 1, 0, 1]]);
});

it('shows the strand in the student record and in the Form 137 data', function () {
    $stem = shSection($this);
    $enrollment = shLearner($this, 11, Strand::Stem);
    $enrollment->update(['section_id' => $stem->id]);

    $this->actingAs($this->staff)->get(route('staff.students.show', $enrollment->student))
        ->assertInertia(fn ($page) => $page->where('student.data.enrollments.0.strand', 'STEM')->where('student.data.enrollments.0.strand_short', 'STEM'));
});
