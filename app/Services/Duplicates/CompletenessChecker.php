<?php

namespace App\Services\Duplicates;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finds incomplete records with the query builder (no table of its own: the
 * answer is always up to date). Each issue has a key, a label and a query.
 */
class CompletenessChecker
{
    /** LRNs were introduced around 2012: records enrolled from then on should have one. */
    public const LRN_SINCE = 2012;

    /** @return array<string, string> issue key => label */
    public function labels(): array
    {
        return [
            'no_enrollment' => 'No enrollment history',
            'missing_lrn' => 'No LRN (enrolled in 2012 or later)',
            'graduated_without_record' => 'Marked graduated, no graduation data',
            'graduation_without_diploma' => 'Graduation data without a diploma number',
            'missing_birthplace' => 'No birthplace',
        ];
    }

    /** @return array<string, int> issue key => number of records */
    public function counts(): array
    {
        return collect($this->labels())
            ->mapWithKeys(fn (string $label, string $key) => [$key => $this->query($key)->count()])
            ->all();
    }

    /** @return Builder<Student> */
    public function query(string $issue): Builder
    {
        $query = Student::query()->where('is_draft', false);

        return match ($issue) {
            'no_enrollment' => $query->whereDoesntHave('enrollments'),
            'missing_lrn' => $query->whereNull('lrn')->whereHas(
                'enrollments.schoolYear', fn (Builder $q) => $q->where('start_year', '>=', self::LRN_SINCE)
            ),
            'graduated_without_record' => $query->where('status', StudentStatus::Graduated)->whereDoesntHave('graduation'),
            'graduation_without_diploma' => $query->whereHas('graduation', fn (Builder $q) => $q->whereNull('diploma_no')),
            'missing_birthplace' => $query->whereNull('birthplace'),
            default => $query->whereKey([]), // unknown issue: no records
        };
    }
}
