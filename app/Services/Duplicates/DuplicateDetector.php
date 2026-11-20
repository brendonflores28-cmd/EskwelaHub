<?php

namespace App\Services\Duplicates;

use App\Models\DuplicateFlag;
use App\Models\Student;
use App\Support\NameIndex;
use Illuminate\Support\Collection;

/**
 * Finds student records that may be the same person: same birthdate and a
 * very similar name (typos, swapped words, a missing middle name).
 *
 * Only learners born on the same day are compared, so the work stays small
 * even with many years of records.
 */
class DuplicateDetector
{
    /** Minimum name similarity (0 to 1) to raise a flag. */
    public const THRESHOLD = 0.85;

    /**
     * Compare all records and save new flags. Dismissed or merged pairs are
     * never opened again. Returns the number of NEW flags.
     */
    public function scan(): int
    {
        $created = 0;

        Student::query()
            ->select(['id', 'name_key', 'birthdate'])
            ->orderBy('birthdate')
            ->get()
            ->groupBy(fn (Student $s) => $s->birthdate->toDateString())
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->each(function (Collection $group) use (&$created): void {
                $students = $group->values();

                for ($i = 0; $i < $students->count(); $i++) {
                    for ($j = $i + 1; $j < $students->count(); $j++) {
                        $score = $this->similarity($students[$i]->name_key, $students[$j]->name_key);
                        if ($score < self::THRESHOLD) {
                            continue;
                        }

                        [$a, $b] = [$students[$i]->id, $students[$j]->id];
                        $flag = DuplicateFlag::firstOrCreate(
                            ['student_a_id' => min($a, $b), 'student_b_id' => max($a, $b)],
                            ['score' => round($score, 3), 'reason' => 'Same birthdate and a very similar name', 'status' => 'open'],
                        );
                        $created += $flag->wasRecentlyCreated ? 1 : 0;
                    }
                }
            });

        return $created;
    }

    /**
     * Existing records that look like a person who is about to be added
     * (an enrollment form or a scan). Shown to staff as a warning.
     *
     * @return Collection<int, Student>
     */
    public function similarTo(?string $lastName, ?string $firstName, ?string $birthdate): Collection
    {
        if (! $lastName || ! $firstName || ! $birthdate) {
            return collect();
        }

        $key = NameIndex::key($lastName, $firstName);

        return Student::query()
            ->whereDate('birthdate', $birthdate)
            ->get()
            ->filter(fn (Student $s) => $this->similarity($key, $s->name_key) >= self::THRESHOLD)
            ->values();
    }

    /** Symmetric similarity of two name keys: the lower of the two directional scores. */
    public function similarity(string $keyA, string $keyB): float
    {
        $a = $keyA === '' ? [] : explode(' ', $keyA);
        $b = $keyB === '' ? [] : explode(' ', $keyB);

        return min(NameIndex::score($a, $b), NameIndex::score($b, $a));
    }
}
