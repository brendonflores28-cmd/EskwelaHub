<?php

namespace App\Services\Reports;

use App\Enums\RequestStatus;
use App\Enums\Sentiment;
use App\Enums\StudentStatus;
use App\Models\DocumentRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Survey;

/**
 * Numbers for the principal dashboard and the Excel export. Query builder and
 * Eloquent only; grouping by month is done in PHP so it works on any database.
 */
class DashboardMetrics
{
    public function __construct(private readonly EnrollmentForecaster $forecaster) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        return [
            'generated_at' => now()->toDateTimeString(),
            'kpis' => $this->kpis(),
            'enrollment' => $this->enrollmentBySchoolYear(),
            'graduates' => $this->graduatesBySchoolYear(),
            'requests' => [
                'by_status' => $this->requestsByStatus(),
                'by_month' => $this->requestsByMonth(),
            ],
            'satisfaction' => $this->satisfaction(),
            'forecast' => $this->forecaster->forecast(),
        ];
    }

    /** @return array<string, int|float|string|null> */
    public function kpis(): array
    {
        $current = SchoolYear::where('is_current', true)->withCount('enrollments')->first();

        return [
            'school_year' => $current?->label,
            'students' => Student::count(),
            'enrolled_now' => $current?->enrollments_count ?? 0,
            'graduates' => Student::where('status', StudentStatus::Graduated)->count(),
            'open_requests' => DocumentRequest::whereIn('status', [
                RequestStatus::Submitted, RequestStatus::UnderReview, RequestStatus::Approved,
                RequestStatus::Processing, RequestStatus::Ready,
            ])->count(),
            'avg_processing_days' => $this->averageProcessingDays(),
        ];
    }

    /** @return list<array{label: string, start_year: int, count: int, is_current: bool}> */
    public function enrollmentBySchoolYear(): array
    {
        return SchoolYear::withCount('enrollments')->orderBy('start_year')->get()
            ->map(fn (SchoolYear $y) => ['label' => $y->label, 'start_year' => $y->start_year, 'count' => $y->enrollments_count, 'is_current' => $y->is_current])
            ->all();
    }

    /** @return list<array{label: string, start_year: int, count: int}> */
    public function graduatesBySchoolYear(): array
    {
        return SchoolYear::withCount('graduations')->orderBy('start_year')->get()
            ->filter(fn (SchoolYear $y) => $y->graduations_count > 0)
            ->map(fn (SchoolYear $y) => ['label' => $y->label, 'start_year' => $y->start_year, 'count' => $y->graduations_count])
            ->values()
            ->all();
    }

    /** @return list<array{status: string, label: string, count: int}> */
    public function requestsByStatus(): array
    {
        return collect(RequestStatus::cases())
            ->map(fn (RequestStatus $s) => ['status' => $s->value, 'label' => $s->label(), 'count' => DocumentRequest::where('status', $s)->count()])
            ->all();
    }

    /** Requests per month for the last 12 months (months with none show 0). @return list<array{month: string, count: int}> */
    public function requestsByMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $counts = DocumentRequest::where('created_at', '>=', $start)->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m'));

        return collect(range(0, 11))
            ->map(fn (int $i) => $start->copy()->addMonths($i)->format('Y-m'))
            ->map(fn (string $month) => ['month' => $month, 'count' => $counts[$month] ?? 0])
            ->all();
    }

    /** @return array{responses: int, overall: float, speed: float, staff: float, sentiment: array<string, int>} */
    public function satisfaction(): array
    {
        return [
            'responses' => Survey::count(),
            'overall' => round((float) Survey::avg('rating_overall'), 2),
            'speed' => round((float) Survey::avg('rating_speed'), 2),
            'staff' => round((float) Survey::avg('rating_staff'), 2),
            'sentiment' => collect(Sentiment::cases())
                ->mapWithKeys(fn (Sentiment $s) => [$s->value => Survey::where('sentiment', $s)->count()])
                ->all(),
        ];
    }

    /** Average days from request to release, for released requests. */
    private function averageProcessingDays(): ?float
    {
        $released = DocumentRequest::where('status', RequestStatus::Released)->whereNotNull('released_at')->get(['created_at', 'released_at']);

        if ($released->isEmpty()) {
            return null;
        }

        return round($released->avg(fn (DocumentRequest $r) => $r->created_at->diffInSeconds($r->released_at) / 86400), 1);
    }
}
