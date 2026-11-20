<?php

namespace App\Services\Reports;

use App\Models\SchoolYear;
use App\Support\LinearRegression;

/**
 * Enrollment forecast with simple linear regression over past school years.
 *
 * The current school year is left out of the fit because it is still in
 * progress. At least 3 finished years are needed, otherwise there is no
 * forecast (a line through two points would only look like a prediction).
 */
class EnrollmentForecaster
{
    public const MIN_YEARS = 3;

    /** How many past school years to use. Old curricula change the numbers, so recent years count more. */
    public const YEARS_BACK = 10;

    /**
     * @return array{history: list<array{label: string, start_year: int, count: int}>, forecast: list<array{label: string, start_year: int, predicted: int}>, slope: float, r2: float, enough_data: bool, note: string}
     */
    public function forecast(int $yearsAhead = 3): array
    {
        $current = SchoolYear::where('is_current', true)->value('start_year');

        $history = SchoolYear::query()
            ->withCount('enrollments')
            ->when($current, fn ($q) => $q->where('start_year', '<', $current))
            ->where('enrollments_count', '>', 0)
            ->orderByDesc('start_year')
            ->limit(self::YEARS_BACK)
            ->get()
            ->sortBy('start_year')
            ->map(fn (SchoolYear $y) => ['label' => $y->label, 'start_year' => $y->start_year, 'count' => $y->enrollments_count])
            ->values()
            ->all();

        if (count($history) < self::MIN_YEARS || $current === null) {
            return [
                'history' => $history, 'forecast' => [], 'slope' => 0.0, 'r2' => 0.0, 'enough_data' => false,
                'note' => 'Not enough finished school years yet (at least '.self::MIN_YEARS.' are needed).',
            ];
        }

        $line = LinearRegression::fit(array_column($history, 'start_year'), array_column($history, 'count'));

        $forecast = [];
        for ($i = 0; $i < $yearsAhead; $i++) {
            $year = $current + $i;
            $forecast[] = [
                'label' => SchoolYear::labelFor($year),
                'start_year' => $year,
                'predicted' => max(0, (int) round($line->predict($year))),
            ];
        }

        return [
            'history' => $history,
            'forecast' => $forecast,
            'slope' => round($line->slope, 2),
            'r2' => round($line->r2, 3),
            'enough_data' => true,
            'note' => 'Straight-line trend over the last '.count($history).' finished school years. It is an estimate: policy changes, new curriculum or a new nearby school are not known to the model.',
        ];
    }
}
