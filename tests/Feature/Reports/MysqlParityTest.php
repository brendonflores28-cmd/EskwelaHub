<?php

use App\Services\Reports\DashboardMetrics;

/*
| The normal test run uses SQLite in memory, but production is MySQL. A few
| queries behave differently, so this test runs the dashboard against the real
| MySQL database when it is available (it is skipped otherwise, for example in CI).
| Run it with: ./vendor/bin/pest --group=mysql
*/
it('runs the dashboard queries on MySQL', function () {
    // phpunit.xml points the default connection at SQLite; aim the "mysql" connection at the local database.
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'eskwelahub']);
    DB::purge('mysql');
    try {
        DB::connection('mysql')->getPdo();
    } catch (Throwable) {
        $this->markTestSkipped('MySQL is not reachable.');
    }

    // Read-only: no data is created or changed in the real database.
    $metrics = app(DashboardMetrics::class)->all();

    expect($metrics)->toHaveKeys(['kpis', 'enrollment', 'graduates', 'requests', 'satisfaction', 'forecast']);
})->group('mysql')->skip(fn () => ! env('RUN_MYSQL_PARITY'), 'Set RUN_MYSQL_PARITY=1 to run against the local MySQL database.');
