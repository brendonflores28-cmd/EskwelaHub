<?php

use App\Enums\RequestStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Models\DocumentRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Notifications\RequestStatusChanged;
use App\Services\Requests\RequestWorkflow;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

/*
| The document request workflow: submit, review, approve (PDF), reject, release.
| The queue is "sync" in tests, so approving really builds the PDF.
*/

beforeEach(function () {
    Storage::fake('private');
    $this->student = Student::factory()->create(['lrn' => '900000000101']);
    $year = SchoolYear::create(['label' => '2020-2021', 'start_year' => 2020, 'is_current' => false]);
    $this->student->enrollments()->create([
        'school_year_id' => $year->id, 'grade_level' => 7, 'curriculum' => 'k12', 'status' => 'enrolled',
    ]);
    $this->parent = parentUser(linkedTo: $this->student);
    $this->staff = staffUser();
});

function submitRequest($test, array $overrides = [])
{
    return $test->actingAs($test->parent)->post(route('portal.requests.store'), array_merge([
        'student_id' => $test->student->id,
        'document_type' => 'form_137',
        'purpose' => 'Transfer to another school',
        'copies' => 1,
    ], $overrides));
}

it('lets a linked parent submit a request and tells them it was received', function () {
    Notification::fake();

    submitRequest($this)->assertRedirect();

    $request = DocumentRequest::firstOrFail();
    expect($request->status)->toBe(RequestStatus::Submitted)
        ->and($request->request_no)->toMatch('/^REQ-\d{4}-\d{6}$/')
        ->and($request->verification_token)->toHaveLength(40)
        ->and($request->histories)->toHaveCount(1);
    Notification::assertSentTo($this->parent, RequestStatusChanged::class,
        fn ($n) => $n->status === RequestStatus::Submitted);
});

it('does not let someone request a record they are not linked to', function () {
    $other = Student::factory()->create();

    submitRequest($this, ['student_id' => $other->id])->assertForbidden();

    expect(DocumentRequest::count())->toBe(0);
});

it('validates the request form', function () {
    submitRequest($this, ['document_type' => 'passport', 'purpose' => '', 'copies' => 99])
        ->assertSessionHasErrors(['document_type', 'purpose', 'copies']);
});

it('runs the whole flow: review, approve with PDF, release, download', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();

    $this->actingAs($this->staff);
    $this->post(route('staff.requests.review', $request))->assertRedirect();
    $this->post(route('staff.requests.approve', $request), ['note' => 'Complete record'])->assertRedirect();

    // The sync queue already ran the job: approved -> processing -> ready.
    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Ready);
    $document = $request->document;
    expect($document)->not->toBeNull();
    Storage::disk('private')->assertExists($document->disk_path);
    expect(Storage::disk('private')->get($document->disk_path))->toStartWith('%PDF')
        ->and(hash('sha256', Storage::disk('private')->get($document->disk_path)))->toBe($document->sha256);

    $this->post(route('staff.requests.release', $request))->assertRedirect();
    expect($request->fresh()->status)->toBe(RequestStatus::Released);

    // The parent gets the file through a short-lived signed link.
    $response = $this->actingAs($this->parent)->get(route('documents.request', $request));
    $response->assertRedirect();
    $this->actingAs($this->parent)->get($response->headers->get('Location'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($request->histories()->pluck('to_status')->all())->toBe(
        ['submitted', 'under_review', 'approved', 'processing', 'ready', 'released'],
    );
});

it('rejects a request only with a reason and the parent cannot download', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();

    $this->actingAs($this->staff)->post(route('staff.requests.reject', $request), [])
        ->assertSessionHasErrors('note');

    $this->post(route('staff.requests.reject', $request), ['note' => 'Records are incomplete'])->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Rejected)
        ->and($request->remarks)->toBe('Records are incomplete');
    $this->actingAs($this->parent)->get(route('documents.request', $request))->assertForbidden();
});

it('refuses status changes the workflow does not allow', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();

    // A submitted request cannot be released.
    $this->actingAs($this->staff)->post(route('staff.requests.release', $request))->assertRedirect();
    expect($request->fresh()->status)->toBe(RequestStatus::Submitted);

    expect(fn () => app(RequestWorkflow::class)->release($request, $this->staff))
        ->toThrow(InvalidRequestTransition::class);
});

it('cannot approve the same request twice', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();
    $workflow = app(RequestWorkflow::class);

    $workflow->approve($request, $this->staff);

    expect(fn () => $workflow->approve($request->fresh(), $this->staff))
        ->toThrow(InvalidRequestTransition::class);
    expect(Storage::disk('private')->allFiles())->toHaveCount(1);
});

it('keeps the PDF locked until the registrar releases it', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();
    app(RequestWorkflow::class)->approve($request, $this->staff); // ready, not released

    $this->actingAs($this->parent)->get(route('documents.request', $request))->assertForbidden();
});

it('rejects unsigned and expired file links', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();
    app(RequestWorkflow::class)->approve($request, $this->staff);
    app(RequestWorkflow::class)->release($request->fresh(), $this->staff);
    $document = $request->fresh()->document;

    $this->actingAs($this->parent)->get(route('documents.file', $document))->assertForbidden();

    $expired = URL::temporarySignedRoute('documents.file', now()->subMinute(), ['document' => $document->id]);
    $this->actingAs($this->parent)->get($expired)->assertForbidden();

    // A signed link is not enough: another parent is still refused by the policy.
    $signed = URL::temporarySignedRoute('documents.file', now()->addMinutes(2), ['document' => $document->id]);
    $this->actingAs(parentUser())->get($signed)->assertForbidden();
});

it('stops other accounts from seeing or processing a request', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();
    $stranger = parentUser();

    $this->actingAs($stranger)->get(route('portal.requests.show', $request))->assertForbidden();
    $this->actingAs($this->parent)->post(route('staff.requests.approve', $request))->assertForbidden();
    $this->actingAs($this->parent)->get(route('staff.requests.index'))->assertForbidden();
});

it('writes requested, approved, released and downloaded to the audit log', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();
    app(RequestWorkflow::class)->approve($request, $this->staff);
    app(RequestWorkflow::class)->release($request->fresh(), $this->staff);
    $url = $this->actingAs($this->parent)->get(route('documents.request', $request))->headers->get('Location');
    $this->actingAs($this->parent)->get($url)->assertOk();

    $events = Activity::where('log_name', 'requests')->orWhere('event', 'downloaded')->pluck('event')->all();
    expect($events)->toContain('requested', 'approved', 'released', 'downloaded');
});

it('shows a public verification page that hides the learner details', function () {
    submitRequest($this);
    $request = DocumentRequest::firstOrFail();

    // Not valid while it is only submitted.
    $this->get(route('verify', $request->verification_token))
        ->assertInertia(fn ($page) => $page->component('public/Verify')->where('valid', false));

    app(RequestWorkflow::class)->approve($request, $this->staff);

    $this->get(route('verify', $request->verification_token))
        ->assertInertia(fn ($page) => $page
            ->where('valid', true)
            ->where('document.request_no', $request->request_no)
            ->missing('document.lrn')
            ->where('document.learner', fn ($name) => str_contains($name, '*') && ! str_contains($name, $this->student->last_name)));

    $this->get(route('verify', 'not-a-real-token'))
        ->assertInertia(fn ($page) => $page->where('valid', false)->where('document', null));
});
