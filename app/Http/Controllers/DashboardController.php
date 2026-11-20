<?php

namespace App\Http\Controllers;

use App\Enums\LinkStatus;
use App\Enums\RequestStatus;
use App\Enums\StudentStatus;
use App\Models\DocumentRequest;
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

        if (! $user->can('students.view')) {
            return Inertia::render('Dashboard', [
                'portal' => 'student',
                'stats' => [
                    'linked_records' => $user->studentLinks()->where('status', LinkStatus::Approved)->count(),
                    'pending_links' => $user->studentLinks()->where('status', LinkStatus::Pending)->count(),
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

        return Inertia::render('Dashboard', [
            'portal' => $user->hasRole('admin') ? 'admin' : 'staff',
            'stats' => [
                'students' => Student::count(),
                'enrolled_now' => $current
                    ? StudentEnrollment::where('school_year_id', $current->id)->count()
                    : 0,
                'graduates' => Student::where('status', StudentStatus::Graduated)->count(),
                'pending_links' => StudentLink::where('status', LinkStatus::Pending)->count(),
                'open_requests' => DocumentRequest::whereIn('status', [
                    RequestStatus::Submitted, RequestStatus::UnderReview, RequestStatus::Approved,
                    RequestStatus::Processing, RequestStatus::Ready,
                ])->count(),
                'school_year' => $current?->label,
            ],
        ]);
    }
}
