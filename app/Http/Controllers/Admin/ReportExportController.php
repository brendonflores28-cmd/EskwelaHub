<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DashboardExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardMetrics;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Principal: download the dashboard numbers as an Excel workbook. */
class ReportExportController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): BinaryFileResponse
    {
        activity('reports')
            ->causedBy(auth()->user())
            ->event('exported')
            ->withProperties(['ip' => request()->ip()])
            ->log('Exported the dashboard to Excel');

        return Excel::download(new DashboardExport($metrics->all()), 'eskwelahub-report-'.now()->format('Y-m-d').'.xlsx');
    }
}
