<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Shared hosting cannot keep `queue:work` running, so the queue is drained
| once a minute. The host cron runs `php artisan schedule:run` every minute
| (see DEPLOY.md). --max-time=50 keeps each run under the cron interval.
*/
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// Remove audit entries older than the retention set in config/activitylog.php (5 years).
Schedule::command('activitylog:clean')->daily();
