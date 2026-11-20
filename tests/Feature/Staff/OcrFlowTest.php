<?php

use App\Contracts\OcrEngine;
use App\Models\OcrDraft;
use App\Models\ScanUpload;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Ocr\BrowserOcrEngine;
use App\Services\Ocr\OcrSpaceEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('private');
    $this->staff = staffUser();
});

const SAMPLE_TEXT = "LRN: 123456789012\nName: SANTOS, Juan Reyes\nSex: Male\nDate of Birth: January 5, 2003";

function uploadScan($test, array $extra = [])
{
    return $test->actingAs($test->staff)->post(route('staff.ocr.store'), array_merge([
        'scan' => UploadedFile::fake()->image('record.jpg'),
        'text' => SAMPLE_TEXT,
    ], $extra));
}

it('keeps the browser engine as the default', function () {
    expect(app(OcrEngine::class))->toBeInstanceOf(BrowserOcrEngine::class);
});

it('creates a draft from the text sent by the browser', function () {
    uploadScan($this)->assertRedirect();

    $draft = OcrDraft::firstOrFail();
    expect($draft->status)->toBe('pending')
        ->and($draft->parsed['last_name'])->toBe('Santos')
        ->and($draft->parsed['lrn'])->toBe('123456789012')
        ->and($draft->scan->engine)->toBe('browser')
        ->and(Student::count())->toBe(0); // nothing is a record until staff verify it
    Storage::disk('private')->assertExists($draft->scan->disk_path);
});

it('creates a record only after staff verify the draft', function () {
    $year = SchoolYear::create(['label' => '2005-2006', 'start_year' => 2005, 'is_current' => false]);
    uploadScan($this);
    $draft = OcrDraft::firstOrFail();

    $this->post(route('staff.ocr.verify', $draft), [
        'lrn' => '123456789012', 'last_name' => 'Santos', 'first_name' => 'Juan', 'middle_name' => 'Reyes',
        'sex' => 'M', 'birthdate' => '2003-01-05', 'school_year_id' => $year->id, 'grade_level' => 7, 'general_average' => '87.5',
    ])->assertRedirect();

    $student = Student::where('lrn', '123456789012')->firstOrFail();
    expect($student->enrollments()->first()->curriculum->value)->toBe('bec')
        ->and($draft->fresh()->status)->toBe('verified')
        ->and($draft->fresh()->student_id)->toBe($student->id);

    // A verified draft cannot be verified again.
    $this->post(route('staff.ocr.verify', $draft), [
        'last_name' => 'Santos', 'first_name' => 'Juan', 'sex' => 'M', 'birthdate' => '2003-01-05',
    ])->assertStatus(409);
    expect(Student::count())->toBe(1);
});

it('validates the verified fields and can discard a draft', function () {
    uploadScan($this);
    $draft = OcrDraft::firstOrFail();

    $this->post(route('staff.ocr.verify', $draft), ['last_name' => '', 'birthdate' => 'not a date'])
        ->assertSessionHasErrors(['last_name', 'first_name', 'sex', 'birthdate']);

    $this->post(route('staff.ocr.reject', $draft))->assertRedirect(route('staff.ocr.index'));
    expect($draft->fresh()->status)->toBe('rejected');
});

it('rejects bad scan files', function () {
    uploadScan($this, ['scan' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain')])->assertSessionHasErrors('scan');
    uploadScan($this, ['scan' => UploadedFile::fake()->create('huge.pdf', 9000, 'application/pdf')])->assertSessionHasErrors('scan');

    expect(ScanUpload::count())->toBe(0);
});

it('keeps scans private: signed link, staff only, logged', function () {
    uploadScan($this);
    $scan = ScanUpload::firstOrFail();

    $this->actingAs($this->staff)->get(route('scans.file', $scan))->assertForbidden(); // unsigned

    $show = $this->actingAs($this->staff)->get(route('staff.ocr.show', OcrDraft::first()));
    $url = $show->inertiaProps('draft.scan_url');
    $this->actingAs($this->staff)->get($url)->assertOk();

    // A parent cannot use even a valid signed link.
    $this->actingAs(parentUser())->get($url)->assertForbidden();
    $this->actingAs(parentUser())->get(route('staff.ocr.index'))->assertForbidden();
    expect(Activity::where('event', 'opened')->count())->toBe(1);
});

it('lets a server engine read the file instead of the browser (OCR.space, faked)', function () {
    config(['ocr.driver' => 'ocr_space', 'ocr.ocr_space.key' => 'test-key']);
    Http::fake(['api.ocr.space/*' => Http::response(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => "Name: GARCIA, Ana\nLRN: 111122223333"]]])]);

    expect(app(OcrEngine::class))->toBeInstanceOf(OcrSpaceEngine::class);

    uploadScan($this, ['text' => 'ignored text from the browser'])->assertRedirect();

    $draft = OcrDraft::firstOrFail();
    expect($draft->parsed['last_name'])->toBe('Garcia')->and($draft->scan->engine)->toBe('ocr_space');
    Http::assertSent(fn ($request) => $request->hasHeader('apikey', 'test-key'));
});

it('falls back to an empty draft when the OCR API fails', function () {
    config(['ocr.driver' => 'ocr_space', 'ocr.ocr_space.key' => 'test-key']);
    Http::fake(['api.ocr.space/*' => Http::response('error', 500)]);

    uploadScan($this)->assertRedirect();

    $draft = OcrDraft::firstOrFail();
    expect($draft->raw_text)->toBeNull()->and($draft->parsed)->toBeNull();
});
