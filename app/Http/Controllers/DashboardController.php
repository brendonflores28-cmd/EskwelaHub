<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\LinkStatus;
use App\Enums\RequestStatus;
use App\Enums\StudentStatus;
use App\Models\DocumentRequest;
use App\Models\DuplicateFlag;
use App\Models\EnrollmentApplication;
use App\Models\OcrDraft;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentLink;
use App\Services\Reports\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** One dashboard route, content depends on the role. */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): Response
    {
        $user = $request->user();

        // Student / parent / alumni: what they have, and what is waiting for the registrar.
        if (! $user->can('students.view')) {
            return Inertia::render('Dashboard', [
                'portal' => 'student',
                'stats' => [
                    'linked_records' => $user->studentLinks()->where('status', LinkStatus::Approved)->count(),
                    'pending_links' => $user->studentLinks()->where('status', LinkStatus::Pending)->count(),
                    'open_requests' => DocumentRequest::where('user_id', $user->id)
                        ->whereNotIn('status', [RequestStatus::Rejected, RequestStatus::Released])->count(),
                    'ready_to_download' => DocumentRequest::where('user_id', $user->id)->where('status', RequestStatus::Released)->count(),
                    'open_enrollments' => EnrollmentApplication::where('user_id', $user->id)
                        ->whereIn('status', [EnrollmentStatus::Submitted, EnrollmentStatus::UnderReview])->count(),
                ],
            ]);
        }

        // The principal gets the full analytics dashboard.
        if ($user->can('reports.view')) {
            return Inertia::render('admin/Dashboard', [
                'metrics' => $metrics->all(),
                'canExport' => $user->can('reports.export'),
            ]);
        }

        $current = SchoolYear::where('is_current', true)->first();

        // Registrar: a to-do list first (what needs action now), then three overview numbers.
        return Inertia::render('Dashboard', [
            'portal' => 'staff',
            'stats' => [
                'todo_requests' => DocumentRequest::whereIn('status', [RequestStatus::Submitted, RequestStatus::UnderReview, RequestStatus::Ready])->count(),
                'todo_enrollments' => EnrollmentApplication::whereIn('status', [EnrollmentStatus::Submitted, EnrollmentStatus::UnderReview])->count(),
                'todo_links' => StudentLink::where('status', LinkStatus::Pending)->count(),
                'todo_alerts' => DuplicateFlag::where('status', 'open')->count(),
                'todo_scans' => OcrDraft::where('status', 'pending')->count(),
                'students' => Student::count(),
                'enrolled_now' => $current ? StudentEnrollment::where('school_year_id', $current->id)->count() : 0,
                'graduates' => Student::where('status', StudentStatus::Graduated)->count(),
                'school_year' => $current?->label,
            ],
        ]);
    }
}
