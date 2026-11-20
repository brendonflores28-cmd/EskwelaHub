<?php

namespace App\Http\Controllers\Staff;

use App\Enums\LinkStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Staff review of requests to link a student/parent account to a student record. */
class LinkRequestController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', StudentLink::class);

        $links = StudentLink::query()
            ->where('status', LinkStatus::Pending)
            ->with(['user:id,name,email', 'student'])
            ->oldest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (StudentLink $link) => [
                'id' => $link->id,
                'relationship' => $link->relationship,
                'requested_at' => $link->created_at->toDateTimeString(),
                'user' => ['name' => $link->user->name, 'email' => $link->user->email],
                'student' => [
                    'id' => $link->student->id,
                    'lrn' => $link->student->lrn,
                    'full_name' => $link->student->full_name,
                    'birthdate' => $link->student->birthdate->toDateString(),
                ],
            ]);

        return Inertia::render('staff/links/Index', ['links' => $links]);
    }

    public function approve(StudentLink $link): RedirectResponse
    {
        return $this->review($link, LinkStatus::Approved, 'Link approved.');
    }

    public function reject(StudentLink $link): RedirectResponse
    {
        return $this->review($link, LinkStatus::Rejected, 'Link rejected.');
    }

    private function review(StudentLink $link, LinkStatus $status, string $message): RedirectResponse
    {
        $this->authorize('review', $link);

        $link->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
