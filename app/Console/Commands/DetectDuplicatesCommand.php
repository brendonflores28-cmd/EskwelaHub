<?php

namespace App\Console\Commands;

use App\Services\Duplicates\DuplicateDetector;
use Illuminate\Console\Command;

class DetectDuplicatesCommand extends Command
{
    protected $signature = 'eskwelahub:detect-duplicates';

    protected $description = 'Find possible duplicate student records';

    public function handle(DuplicateDetector $detector): int
    {
        $new = $detector->scan();
        $this->info("Done. {$new} new possible duplicate(s) found.");

        return self::SUCCESS;
    }
}
