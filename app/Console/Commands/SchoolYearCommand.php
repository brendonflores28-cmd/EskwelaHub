<?php

namespace App\Console\Commands;

use App\Models\SchoolYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates a school year and can make it the current one. Online enrollment and
 * the dashboard need exactly one current school year, and production has no
 * demo seeder to create it.
 */
class SchoolYearCommand extends Command
{
    protected $signature = 'eskwelahub:school-year
        {start_year : Year the school year starts, for example 2026 for 2026-2027}
        {--current : Make it the current school year (the others stop being current)}';

    protected $description = 'Create a school year and optionally make it the current one';

    public function handle(): int
    {
        $start = (int) $this->argument('start_year');
        $first = (int) config('eskwelahub.first_school_year');

        if ($start < $first || $start > now()->year + 1) {
            $this->error("The start year must be between {$first} and ".(now()->year + 1).'.');

            return self::FAILURE;
        }

        $year = DB::transaction(function () use ($start): SchoolYear {
            $year = SchoolYear::firstOrCreate(['start_year' => $start], ['label' => SchoolYear::labelFor($start), 'is_current' => false]);

            if ($this->option('current')) {
                SchoolYear::where('is_current', true)->update(['is_current' => false]);
                $year->update(['is_current' => true]);
            }

            return $year;
        });

        $this->info("School year {$year->label} is ready".($year->is_current ? ' and is now the current school year.' : '.'));

        return self::SUCCESS;
    }
}
