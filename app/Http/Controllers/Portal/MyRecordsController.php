<?php

namespace App\Http\Controllers\Portal;

use App\Enums\LinkStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\StudentLink;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student / parent / alumni portal. A user only sees students whose link to
 * their account was approved by staff.
 */
class MyRecordsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $links = $request->user()->studentLinks()->with('student')->latest()->get();

        return Inertia::render('portal/Records', [
            'approved' => $links
                ->where('status', LinkStatus::Approved)
                ->map(fn (StudentLink $link) => [
                    'student_id' => $link->student_id,
                    'full_name' => $link->student->full_name,
                    'lrn' => $link->student->lrn,
                    'relationship' => $link->relationship,
                ])->values(),
            'pending' => $links->where('status', LinkStatus::Pending)->count(),
            'rejected' => $links->where('status', LinkStatus::Rejected)->count(),
        ]);
    }

    public function show(Student $student): Response
    {
        $this->authorize('view', $student);
        $this->audit->viewed($student, 'Viewed own student record');

        $student->load(['enrollments.schoolYear', 'enrollments.section', 'graduation.schoolYear']);

        return Inertia::render('portal/RecordShow', [
            'student' => new StudentResource($student),
        ]);
    }

    /**
     * Ask staff to link this account to a student record. The learner must be
     * identified by LRN and birthdate. The error message is the same whether the
     * LRN exists or not, so the form cannot be used to discover which LRNs are real.
     */
    public function requestLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lrn' => ['required', 'digits:12'],
            'birthdate' => ['required', 'date'],
            'relationship' => ['required', Rule::in(['self', 'parent', 'guardian'])],
        ]);

        $student = Student::query()
            ->where('lrn', $data['lrn'])
            ->whereDate('birthdate', $data['birthdate'])
            ->first();

        if (! $student) {
            return back()->withErrors(['lrn' => 'We could not match that LRN and birthdate. Check them or ask the registrar.']);
        }

        $link = StudentLink::firstOrCreate(
            ['user_id' => $request->user()->id, 'student_id' => $student->id],
            ['relationship' => $data['relationship'], 'status' => LinkStatus::Pending],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $link->wasRecentlyCreated
                ? 'Request sent. The registrar will review it.'
                : 'You already sent a request for this record.',
        ]);

        return back();
    }
}
