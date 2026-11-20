<?php

namespace App\Jobs;

use App\Services\Duplicates\DuplicateDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Looks for possible duplicate student records. Scheduled once a day. */
class DetectDuplicates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public function handle(DuplicateDetector $detector): void
    {
        $detector->scan();
    }
}
