<?php

namespace App\Http\Controllers\Staff;

use App\Contracts\OcrEngine;
use App\Enums\Curriculum;
use App\Enums\Strand;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\OcrDraft;
use App\Models\ScanUpload;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\SecureFileUrl;
use App\Services\Duplicates\DuplicateDetector;
use App\Services\Ocr\OcrTextParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Digitize old paper records: upload a scan, get text (from the browser's
 * Tesseract.js or a server engine), review the guessed fields, then verify.
 * No student record exists until a staff member verifies the draft.
 */
class OcrController extends Controller
{
    public function __construct(
        private readonly OcrEngine $engine,
        private readonly OcrTextParser $parser,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): Response
    {
        $drafts = OcrDraft::query()
            ->with(['scan.uploader:id,name'])
            ->orderBy('status') // "pending" sorts before "rejected" and "verified"
            ->latest()
            ->paginate(15)
            ->through(fn (OcrDraft $d) => [
                'id' => $d->id,
                'file' => $d->scan->original_name,
                'status' => $d->status,
                'uploaded_by' => $d->scan->uploader?->name,
                'at' => $d->created_at->toDateTimeString(),
                'student_id' => $d->student_id,
            ]);

        return Inertia::render('staff/ocr/Index', ['drafts' => $drafts]);
    }

    public function create(): Response
    {
        return Inertia::render('staff/ocr/Create', [
            'engine' => ['name' => $this->engine->name(), 'runsOnServer' => $this->engine->runsOnServer()],
            'maxKb' => config('ocr.max_kb'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scan' => ['required', 'file', 'mimes:'.implode(',', config('ocr.mimes')), 'max:'.config('ocr.max_kb')],
            'text' => ['nullable', 'string', 'max:100000'], // text found by Tesseract.js in the browser
        ]);

        $file = $data['scan'];
        $path = $file->storeAs('scans', Str::random(40).'.'.$file->extension(), 'private');

        $text = $this->engine->runsOnServer()
            ? $this->engine->extract(Storage::disk('private')->path($path), $file->getMimeType() ?? '')
            : ($data['text'] ?? null);

        $draft = DB::transaction(function () use ($request, $file, $path, $text): OcrDraft {
            $scan = ScanUpload::create([
                'uploaded_by' => $request->user()->id,
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 120, ''),
                'disk_path' => $path,
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'engine' => $this->engine->name(),
            ]);

            return $scan->draft()->create([
                'raw_text' => $text,
                'parsed' => $text ? $this->parser->parse($text) : null,
                'status' => 'pending',
            ]);
        });

        $this->audit->record($draft->scan, 'uploaded', 'Uploaded a scan for digitization');

        return to_route('staff.ocr.show', $draft);
    }

    public function show(OcrDraft $draft, SecureFileUrl $urls, DuplicateDetector $detector): Response
    {
        $draft->load('scan');

        return Inertia::render('staff/ocr/Show', [
            'draft' => [
                'id' => $draft->id,
                'status' => $draft->status,
                'raw_text' => $draft->raw_text,
                'parsed' => $draft->parsed ?? [],
                'student_id' => $draft->student_id,
                'file' => $draft->scan->original_name,
                'is_image' => str_starts_with($draft->scan->mime, 'image/'),
                // Ten minutes: the scan stays on screen while staff check the fields.
                'scan_url' => $urls->signed('scans.file', ['scan' => $draft->scan->id], 10),
            ],
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(['id', 'label']),
            'strands' => Strand::options(),
            // Look-alike records that already exist: saving a draft may create a duplicate.
            'similar' => $detector->similarTo($draft->parsed['last_name'] ?? null, $draft->parsed['first_name'] ?? null, $draft->parsed['birthdate'] ?? null)
                ->map(fn ($s) => ['id' => $s->id, 'full_name' => $s->full_name, 'lrn' => $s->lrn, 'birthdate' => $s->birthdate->toDateString()]),
        ]);
    }

    /** The staff member checked the fields: create the real student record. */
    public function verify(Request $request, OcrDraft $draft): RedirectResponse
    {
        abort_unless($draft->status === 'pending', 409, 'This draft was already handled.');

        $data = $request->validate([
            'lrn' => ['nullable', 'digits:12', 'unique:students,lrn'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'birthdate' => ['required', 'date', 'after:1940-01-01', 'before:today'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'grade_level' => ['nullable', 'integer', 'between:7,12', 'required_with:school_year_id'],
            'general_average' => ['nullable', 'numeric', 'between:60,100'],
            'strand' => ['nullable', Rule::in(Strand::offeredValues())],
        ]);

        $student = DB::transaction(function () use ($data, $draft, $request): Student {
            $student = Student::create([
                'lrn' => $data['lrn'] ?? null,
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'sex' => $data['sex'],
                'birthdate' => $data['birthdate'],
                'status' => StudentStatus::Enrolled,
            ]);

            if (! empty($data['school_year_id'])) {
                $year = SchoolYear::find($data['school_year_id']);
                $student->enrollments()->create([
                    'school_year_id' => $year->id,
                    'grade_level' => $data['grade_level'],
                    // Only Grades 11 and 12 have a strand (ignored for junior high).
                    'strand' => Strand::appliesTo((int) $data['grade_level']) ? ($data['strand'] ?? null) : null,
                    'curriculum' => Curriculum::forLevel($year->start_year, (int) $data['grade_level']),
                    'status' => StudentStatus::Enrolled,
                    'general_average' => $data['general_average'] ?? null,
                ]);
            }

            $draft->update([
                'status' => 'verified',
                'student_id' => $student->id,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            return $student;
        });

        $this->audit->record($draft->scan, 'verified', "Verified scan and created student record #{$student->id}");
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Record verified and saved.']);

        return to_route('staff.students.show', $student);
    }

    public function reject(Request $request, OcrDraft $draft): RedirectResponse
    {
        abort_unless($draft->status === 'pending', 409, 'This draft was already handled.');

        $draft->update(['status' => 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now()]);
        $this->audit->record($draft->scan, 'rejected', 'Rejected an OCR draft');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft discarded.']);

        return to_route('staff.ocr.index');
    }

    /** Streams the scan. Signed link plus permission, and every open is logged. */
    public function scanFile(ScanUpload $scan): StreamedResponse
    {
        $this->audit->record($scan, 'opened', 'Opened a scan');

        return Storage::disk('private')->response($scan->disk_path, $scan->original_name, [
            'Content-Type' => $scan->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
