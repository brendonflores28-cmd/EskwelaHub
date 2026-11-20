<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\DuplicateFlag;
use App\Models\Student;
use App\Services\Duplicates\CompletenessChecker;
use App\Services\Duplicates\DuplicateDetector;
use App\Services\Duplicates\StudentMerger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Alerts for staff: possible duplicate records and incomplete records. */
class RecordAlertsController extends Controller
{
    public function index(Request $request, CompletenessChecker $completeness): Response
    {
        $this->authorize('create', Student::class); // same permission as editing records

        $issue = $request->validate(['issue' => ['nullable', 'string']])['issue'] ?? null;

        $duplicates = DuplicateFlag::query()
            ->where('status', 'open')
            ->with(['studentA.latestEnrollment.schoolYear', 'studentB.latestEnrollment.schoolYear'])
            ->orderByDesc('score')
            ->limit(50)
            ->get()
            ->map(fn (DuplicateFlag $f) => [
                'id' => $f->id,
                'score' => (float) $f->score,
                'reason' => $f->reason,
                'a' => (new StudentResource($f->studentA))->resolve(),
                'b' => (new StudentResource($f->studentB))->resolve(),
            ]);

        return Inertia::render('staff/alerts/Index', [
            'duplicates' => $duplicates,
            'counts' => $completeness->counts(),
            'labels' => $completeness->labels(),
            'issue' => $issue,
            'incomplete' => $issue
                ? StudentResource::collection($completeness->query($issue)->with('latestEnrollment.schoolYear')->orderBy('last_name')->paginate(15)->withQueryString())
                : null,
        ]);
    }

    /** Run the duplicate check now instead of waiting for the nightly job. */
    public function scan(DuplicateDetector $detector): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $new = $detector->scan();
        Inertia::flash('toast', ['type' => 'success', 'message' => "Check finished: {$new} new possible duplicate(s)."]);

        return back();
    }

    public function dismiss(Request $request, DuplicateFlag $flag): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $flag->update(['status' => 'dismissed', 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marked as two different people.']);

        return back();
    }

    /** Merge the pair, keeping the record the staff member picked ("keep" = its id). */
    public function merge(Request $request, DuplicateFlag $flag, StudentMerger $merger): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $keepId = (int) $request->validate(['keep' => ['required', 'integer']])['keep'];
        abort_unless(in_array($keepId, [$flag->student_a_id, $flag->student_b_id], true), 422);
        abort_unless($flag->status === 'open', 409);

        $keep = Student::findOrFail($keepId);
        $remove = Student::findOrFail($keepId === $flag->student_a_id ? $flag->student_b_id : $flag->student_a_id);

        $merger->merge($keep, $remove, $request->user(), $flag);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Records merged.']);

        return to_route('staff.students.show', $keep);
    }
}
