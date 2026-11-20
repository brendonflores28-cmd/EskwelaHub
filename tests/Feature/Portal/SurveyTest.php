<?php

use App\Enums\EnrollmentStatus;
use App\Enums\RequestStatus;
use App\Enums\Sentiment;
use App\Models\DocumentRequest;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Survey;

beforeEach(function () {
    $this->student = Student::factory()->create();
    $this->parent = parentUser(linkedTo: $this->student);
    $this->request = DocumentRequest::create([
        'user_id' => $this->parent->id, 'student_id' => $this->student->id,
        'document_type' => 'form_137', 'purpose' => 'Transfer', 'copies' => 1,
    ]);
});

function surveyData(array $overrides = []): array
{
    return array_merge(['rating_overall' => 5, 'rating_speed' => 4, 'rating_staff' => 5, 'comment' => 'Mabilis at maayos, salamat po'], $overrides);
}

function release(DocumentRequest $request): void
{
    $request->forceFill(['status' => RequestStatus::Released])->save();
}

it('saves a survey with a sentiment label after the document was released', function () {
    release($this->request);

    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData())->assertRedirect();

    $survey = Survey::firstOrFail();
    expect($survey->rating_overall)->toBe(5)
        ->and($survey->sentiment)->toBe(Sentiment::Positive)
        ->and((float) $survey->sentiment_score)->toBeGreaterThan(0)
        ->and($survey->document_request_id)->toBe($this->request->id);
});

it('labels a negative comment and leaves the label empty without a comment', function () {
    release($this->request);
    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData(['comment' => 'Sobrang bagal, nakakainis']));
    expect(Survey::first()->sentiment)->toBe(Sentiment::Negative);

    Survey::query()->delete();
    $this->post(route('portal.requests.survey', $this->request), surveyData(['comment' => '']));
    expect(Survey::first()->sentiment)->toBeNull()->and(Survey::first()->comment)->toBeNull();
});

it('asks only once, only after release, only the requester', function () {
    // Not released yet.
    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData())->assertForbidden();

    release($this->request);
    $this->actingAs(parentUser())->post(route('portal.requests.survey', $this->request), surveyData())->assertForbidden();

    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData())->assertRedirect();
    $this->post(route('portal.requests.survey', $this->request), surveyData())->assertSessionHasErrors('rating_overall');
    expect(Survey::count())->toBe(1);
});

it('validates the ratings and comment length', function () {
    release($this->request);

    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData(['rating_overall' => 9, 'rating_speed' => 0, 'comment' => str_repeat('a', 1001)]))
        ->assertSessionHasErrors(['rating_overall', 'rating_speed', 'comment']);
});

it('offers the survey on the request page once', function () {
    release($this->request);

    $this->actingAs($this->parent)->get(route('portal.requests.show', $this->request))
        ->assertInertia(fn ($page) => $page->where('canSurvey', true));

    $this->post(route('portal.requests.survey', $this->request), surveyData());

    $this->get(route('portal.requests.show', $this->request))
        ->assertInertia(fn ($page) => $page->where('canSurvey', false));
});

it('works after an enrollment decision too', function () {
    $year = SchoolYear::create(['label' => '2026-2027', 'start_year' => 2026, 'is_current' => true]);
    $application = EnrollmentApplication::create([
        'user_id' => $this->parent->id, 'school_year_id' => $year->id, 'type' => 'grade7', 'grade_level' => 7,
        'relationship' => 'parent', 'last_name' => 'Garcia', 'first_name' => 'Ana', 'sex' => 'F', 'birthdate' => '2014-01-01',
        'address' => 'Cawayan', 'guardian_name' => 'Rosa', 'guardian_contact' => '09171234567',
    ]);

    // Still open: no survey.
    $this->actingAs($this->parent)->post(route('portal.enrollments.survey', $application), surveyData())->assertForbidden();

    $application->forceFill(['status' => EnrollmentStatus::Approved])->save();
    $this->post(route('portal.enrollments.survey', $application), surveyData())->assertRedirect();

    expect(Survey::where('enrollment_application_id', $application->id)->count())->toBe(1);
});

it('shows the surveys to the admin only', function () {
    release($this->request);
    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData());
    $this->actingAs($this->parent)->post(route('portal.requests.survey', $this->request), surveyData()); // duplicate, ignored

    $this->actingAs(staffUser())->get(route('admin.surveys'))->assertForbidden();
    $this->actingAs(parentUser())->get(route('admin.surveys'))->assertForbidden();

    $this->actingAs(adminUser())->get(route('admin.surveys', ['sentiment' => 'positive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Surveys')
            ->where('summary.total', 1)
            ->where('summary.positive', 1)
            ->has('surveys.data', 1));

    $this->get(route('admin.surveys', ['sentiment' => 'negative']))->assertInertia(fn ($page) => $page->has('surveys.data', 0));
});
