<?php

use App\Support\LinearRegression;

it('finds a perfect line', function () {
    $line = LinearRegression::fit([1, 2, 3, 4], [3, 5, 7, 9]); // y = 2x + 1

    expect($line->slope)->toEqualWithDelta(2.0, 1e-9)
        ->and($line->intercept)->toEqualWithDelta(1.0, 1e-9)
        ->and($line->r2)->toEqualWithDelta(1.0, 1e-9)
        ->and($line->predict(10))->toEqualWithDelta(21.0, 1e-9);
});

it('matches a hand-calculated example', function () {
    // x = 1..5, y = 2,4,5,4,5: slope 0.6, intercept 2.2, r2 0.6 (textbook example)
    $line = LinearRegression::fit([1, 2, 3, 4, 5], [2, 4, 5, 4, 5]);

    expect($line->slope)->toEqualWithDelta(0.6, 1e-9)
        ->and($line->intercept)->toEqualWithDelta(2.2, 1e-9)
        ->and($line->r2)->toEqualWithDelta(0.6, 1e-9)
        ->and($line->n)->toBe(5);
});

it('works with school years as x (large x values)', function () {
    $line = LinearRegression::fit([2020, 2021, 2022, 2023], [100, 110, 120, 130]);

    expect($line->predict(2024))->toEqualWithDelta(140.0, 1e-6);
});

it('handles a falling trend', function () {
    $line = LinearRegression::fit([1, 2, 3], [30, 20, 10]);

    expect($line->slope)->toEqualWithDelta(-10.0, 1e-9)->and($line->predict(4))->toEqualWithDelta(0.0, 1e-9);
});

it('predicts the average when there is no trend to find', function () {
    $single = LinearRegression::fit([2020], [120]);
    $same = LinearRegression::fit([2020, 2020, 2020], [100, 110, 120]);
    $flat = LinearRegression::fit([1, 2, 3], [50, 50, 50]);

    expect($single->predict(2030))->toBe(120.0)
        ->and($same->predict(1999))->toBe(110.0)
        ->and($flat->slope)->toBe(0.0)->and($flat->r2)->toBe(1.0);
});

it('refuses bad input', function () {
    expect(fn () => LinearRegression::fit([], []))->toThrow(InvalidArgumentException::class)
        ->and(fn () => LinearRegression::fit([1, 2], [1]))->toThrow(InvalidArgumentException::class);
});
