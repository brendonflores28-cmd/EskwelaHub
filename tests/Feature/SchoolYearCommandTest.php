<?php

use App\Models\SchoolYear;

it('creates a school year and makes it the only current one', function () {
    SchoolYear::create(['label' => '2025-2026', 'start_year' => 2025, 'is_current' => true]);

    $this->artisan('eskwelahub:school-year', ['start_year' => 2026, '--current' => true])
        ->expectsOutputToContain('2026-2027')->assertSuccessful();

    expect(SchoolYear::where('is_current', true)->pluck('label')->all())->toBe(['2026-2027'])
        ->and(SchoolYear::count())->toBe(2);
});

it('can be run twice and does not make a year current unless asked', function () {
    $this->artisan('eskwelahub:school-year', ['start_year' => 2026])->assertSuccessful();
    $this->artisan('eskwelahub:school-year', ['start_year' => 2026])->assertSuccessful();

    expect(SchoolYear::count())->toBe(1)->and(SchoolYear::first()->is_current)->toBeFalse();
});

it('refuses impossible years', function () {
    $this->artisan('eskwelahub:school-year', ['start_year' => 1850])->assertFailed();
    $this->artisan('eskwelahub:school-year', ['start_year' => 2999])->assertFailed();

    expect(SchoolYear::count())->toBe(0);
});
