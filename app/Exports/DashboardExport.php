<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Excel workbook of the principal dashboard: one sheet per topic.
 * Built from the same numbers as the dashboard page (DashboardMetrics::all()).
 */
class DashboardExport implements Export, WithMultipleSheets
{
    /** @param  array<string, mixed>  $metrics */
    public function __construct(private readonly array $metrics) {}

    /** @return list<ArraySheet> */
    public function sheets(): array
    {
        $m = $this->metrics;
        $k = $m['kpis'];
        $f = $m['forecast'];

        return [
            new ArraySheet('Summary', ['Measure', 'Value'], [
                ['School year', $k['school_year']],
                ['Student records', $k['students']],
                ['Enrolled this school year', $k['enrolled_now']],
                ['Graduates on file', $k['graduates']],
                ['Open document requests', $k['open_requests']],
                ['Average days to release', $k['avg_processing_days'] ?? 'n/a'],
                ['Survey responses', $m['satisfaction']['responses']],
                ['Average satisfaction (1-5)', $m['satisfaction']['overall']],
                ['Report created', $m['generated_at']],
            ]),
            new ArraySheet('Enrollment', ['School year', 'Learners enrolled'],
                array_map(fn ($r) => [$r['label'], $r['count']], $m['enrollment'])),
            new ArraySheet('Graduates', ['School year', 'Graduates'],
                array_map(fn ($r) => [$r['label'], $r['count']], $m['graduates'])),
            new ArraySheet('Requests', ['Status / month', 'Requests'], [
                ...array_map(fn ($r) => ['Status: '.$r['label'], $r['count']], $m['requests']['by_status']),
                ...array_map(fn ($r) => ['Month: '.$r['month'], $r['count']], $m['requests']['by_month']),
            ]),
            new ArraySheet('Senior high', ['Strand', 'Grade 11', 'Grade 12', 'Total'],
                array_map(fn ($r) => [$r['label'], $r['grade11'], $r['grade12'], $r['total']], $m['strands'])),
            new ArraySheet('Satisfaction', ['Measure', 'Value'], [
                ['Overall rating', $m['satisfaction']['overall']],
                ['Speed rating', $m['satisfaction']['speed']],
                ['Staff rating', $m['satisfaction']['staff']],
                ['Positive comments', $m['satisfaction']['sentiment']['positive']],
                ['Neutral comments', $m['satisfaction']['sentiment']['neutral']],
                ['Negative comments', $m['satisfaction']['sentiment']['negative']],
            ]),
            new ArraySheet('Forecast', ['School year', 'Type', 'Learners'], [
                ...array_map(fn ($r) => [$r['label'], 'Actual', $r['count']], $f['history']),
                ...array_map(fn ($r) => [$r['label'], 'Forecast', $r['predicted']], $f['forecast']),
                ['', '', ''],
                ['Note', $f['note'], ''],
            ]),
        ];
    }
}
