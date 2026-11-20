<?php

use App\Enums\RequestStatus;
use App\Exports\ArraySheet;
use App\Exports\DashboardExport;
use App\Models\DocumentRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Survey;
use App\Services\Reports\DashboardMetrics;
use App\Services\Reports\EnrollmentForecaster;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

/** Creates school years with the given number of enrolled learners each. @param array<int, int> $counts start year => learners */
function seedEnrollment(array $counts, int $currentYear): void
{
    foreach ($counts as $year => $learners) {
        $schoolYear = SchoolYear::create([
            'label' => SchoolYear::labelFor($year), 'start_year' => $year, 'is_current' => $year === $currentYear,
        ]);
        Student::factory()->count($learners)->create()->each(
            fn (Student $s) => $s->enrollments()->create(['school_year_id' => $schoolYear->id, 'grade_level' => 7, 'curriculum' => 'k12', 'status' => 'enrolled']),
        );
    }
}

it('forecasts the next school years from the finished ones', function () {
    // 10, 12, 14, 16 learners in 2022 to 2025; 2026 is the current year and is ignored in the fit.
    seedEnrollment([2022 => 10, 2023 => 12, 2024 => 14, 2025 => 16, 2026 => 3], currentYear: 2026);

    $result = app(EnrollmentForecaster::class)->forecast(3);

    expect($result['enough_data'])->toBeTrue()
        ->and(array_column($result['history'], 'count'))->toBe([10, 12, 14, 16])
        ->and($result['slope'])->toEqualWithDelta(2.0, 0.001)
        ->and($result['r2'])->toEqualWithDelta(1.0, 0.001)
        ->and(array_column($result['forecast'], 'label'))->toBe(['2026-2027', '2027-2028', '2028-2029'])
        ->and(array_column($result['forecast'], 'predicted'))->toBe([18, 20, 22]);
});

it('does not forecast with fewer than 3 finished years', function () {
    seedEnrollment([2024 => 10, 2025 => 12, 2026 => 5], currentYear: 2026);

    $result = app(EnrollmentForecaster::class)->forecast();

    expect($result['enough_data'])->toBeFalse()
        ->and($result['forecast'])->toBe([])
        ->and($result['note'])->toContain('Not enough');
});

it('never predicts a negative number of learners', function () {
    seedEnrollment([2022 => 12, 2023 => 8, 2024 => 4, 2025 => 1, 2026 => 1], currentYear: 2026);

    $predicted = array_column(app(EnrollmentForecaster::class)->forecast(5)['forecast'], 'predicted');

    expect(min($predicted))->toBeGreaterThanOrEqual(0);
});

it('collects the dashboard numbers', function () {
    seedEnrollment([2025 => 3, 2026 => 2], currentYear: 2026);
    $parent = parentUser(linkedTo: Student::first());
    $student = Student::first();

    $make = fn (RequestStatus $status, $createdAt, $releasedAt = null) => DocumentRequest::create([
        'user_id' => $parent->id, 'student_id' => $student->id, 'document_type' => 'form_137', 'purpose' => 'x', 'copies' => 1,
    ])->forceFill(['status' => $status, 'created_at' => $createdAt, 'released_at' => $releasedAt])->save();
    $make(RequestStatus::Submitted, now());
    $make(RequestStatus::Released, now()->subDays(4), now()->subDays(2)); // took 2 days
    $make(RequestStatus::Released, now()->subDays(10), now()->subDays(6)); // took 4 days

    Survey::create(['user_id' => $parent->id, 'document_request_id' => DocumentRequest::first()->id, 'rating_overall' => 5, 'rating_speed' => 4, 'rating_staff' => 3, 'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1]);

    $m = app(DashboardMetrics::class)->all();

    expect($m['kpis']['students'])->toBe(5)
        ->and($m['kpis']['enrolled_now'])->toBe(2)
        ->and($m['kpis']['open_requests'])->toBe(1)
        ->and($m['kpis']['avg_processing_days'])->toBe(3.0)
        ->and(collect($m['requests']['by_status'])->firstWhere('status', 'released')['count'])->toBe(2)
        ->and($m['requests']['by_month'])->toHaveCount(12)                          // empty months count as 0
        ->and(array_sum(array_column($m['requests']['by_month'], 'count')))->toBe(3)
        ->and($m['satisfaction'])->toMatchArray(['responses' => 1, 'overall' => 5.0, 'speed' => 4.0, 'staff' => 3.0])
        ->and($m['satisfaction']['sentiment']['positive'])->toBe(1)
        ->and(collect($m['enrollment'])->firstWhere('is_current', true)['count'])->toBe(2);
});

it('shows the principal dashboard to the admin and the simple one to staff', function () {
    seedEnrollment([2025 => 2, 2026 => 1], currentYear: 2026);

    $this->actingAs(adminUser())->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Dashboard')->has('metrics.forecast')->where('canExport', true));

    $this->actingAs(staffUser())->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->component('Dashboard')->where('portal', 'staff')->missing('metrics'));

    $this->actingAs(parentUser())->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->component('Dashboard')->where('portal', 'student'));
});

it('exports an Excel workbook with one sheet per topic', function () {
    seedEnrollment([2022 => 10, 2023 => 12, 2024 => 14, 2025 => 16, 2026 => 3], currentYear: 2026);

    $workbook = new DashboardExport(app(DashboardMetrics::class)->all());
    $titles = array_map(fn (ArraySheet $s) => $s->title(), $workbook->sheets());

    expect($titles)->toBe(['Summary', 'Enrollment', 'Graduates', 'Requests', 'Satisfaction', 'Forecast']);
    $forecast = collect($workbook->sheets()[5]->array());
    expect($forecast->where(1, 'Forecast')->pluck(2)->all())->toBe([18, 20, 22])
        ->and($forecast->where(1, 'Actual')->count())->toBe(4)
        ->and($workbook->sheets()[1]->array())->toHaveCount(5);
});

it('lets only the admin download the Excel file and logs it', function () {
    seedEnrollment([2025 => 2, 2026 => 1], currentYear: 2026);

    $this->actingAs(staffUser())->get(route('admin.dashboard.export'))->assertForbidden();
    $this->actingAs(parentUser())->get(route('admin.dashboard.export'))->assertForbidden();
    $this->get(route('admin.dashboard.export'))->assertForbidden();

    $response = $this->actingAs(adminUser())->get(route('admin.dashboard.export'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('eskwelahub-report-')->toContain('.xlsx')
        ->and(file_get_contents($response->getFile()->getPathname(), false, null, 0, 2))->toBe('PK'); // .xlsx is a zip file
    expect(Activity::where('event', 'exported')->count())->toBe(1);
});

it('hands the export to the Excel package with the right workbook', function () {
    Excel::fake();

    $this->actingAs(adminUser())->get(route('admin.dashboard.export'));

    Excel::assertDownloaded('eskwelahub-report-'.now()->format('Y-m-d').'.xlsx', fn (DashboardExport $e) => count($e->sheets()) === 6);
});
