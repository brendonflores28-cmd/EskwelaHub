<?php

namespace App\Services\Search;

use App\Models\Student;
use App\Support\NameIndex;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Student search by LRN, by name (typo tolerant) and by school year.
 *
 * Only the Eloquent query builder is used. Names are matched in two steps:
 *  1. the database narrows candidates with LIKE on name_key / name_metaphone;
 *  2. PHP ranks them with NameIndex::score() and drops weak matches.
 */
class StudentSearch
{
    /**
     * @param  array{q?: ?string, school_year_id?: ?int, grade_level?: ?int, status?: ?string}  $filters
     */
    public function search(array $filters, ?int $page = null): PaginatorContract
    {
        $perPage = (int) config('eskwelahub.search.per_page');
        $term = trim((string) ($filters['q'] ?? ''));

        $query = $this->baseQuery($filters);

        if ($term === '') {
            return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage)->withQueryString();
        }

        // A run of digits is an LRN (or the start of one).
        if (preg_match('/^\d{4,12}$/', $term)) {
            return $query->where('lrn', 'like', $term.'%')->orderBy('lrn')->paginate($perPage)->withQueryString();
        }

        return $this->searchByName($query, $term, $perPage, $page);
    }

    /** @param  array<string, mixed>  $filters */
    private function baseQuery(array $filters): Builder
    {
        return Student::query()
            ->with(['latestEnrollment.schoolYear', 'latestEnrollment.section'])
            ->when($filters['school_year_id'] ?? null, fn (Builder $q, $year) => $q->whereHas(
                'enrollments', fn (Builder $e) => $e->where('school_year_id', $year)
            ))
            ->when($filters['grade_level'] ?? null, fn (Builder $q, $grade) => $q->whereHas(
                'enrollments', fn (Builder $e) => $e->where('grade_level', $grade)
            ))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }

    private function searchByName(Builder $query, string $term, int $perPage, ?int $page): LengthAwarePaginator
    {
        $tokens = NameIndex::tokens($term);
        $codes = array_filter(
            explode(' ', NameIndex::metaphone(implode(' ', $tokens))),
            fn (string $code) => strlen($code) >= 2,
        );

        // Step 1: cheap candidate selection in the database.
        $candidates = $query
            ->where(function (Builder $q) use ($tokens, $codes): void {
                foreach ($tokens as $token) {
                    // Start and end of each word: a typo in the middle of a name still matches.
                    $edge = min(4, strlen($token));
                    $q->orWhere('name_key', 'like', '%'.substr($token, 0, $edge).'%')
                        ->orWhere('name_key', 'like', '%'.substr($token, -$edge).'%');
                }
                foreach ($codes as $code) {
                    $q->orWhere('name_metaphone', 'like', '%'.$code.'%');
                }
            })
            ->limit((int) config('eskwelahub.search.candidate_limit'))
            ->get();

        // Step 2: rank in PHP and keep the good matches.
        $minScore = (float) config('eskwelahub.search.min_score');
        $ranked = $candidates
            ->map(fn (Student $student) => [
                'student' => $student,
                'score' => NameIndex::score($tokens, explode(' ', $student->name_key)),
            ])
            ->filter(fn (array $row) => $row['score'] >= $minScore)
            ->sortByDesc('score')
            ->pluck('student')
            ->values();

        $page ??= LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $ranked->forPage($page, $perPage)->values(),
            $ranked->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        ))->withQueryString();
    }
}
