<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Helpers for typo-tolerant name matching without raw SQL or MySQL extensions.
 *
 * Strategy: every student stores a normalized `name_key` and a phonetic
 * `name_metaphone`. The database narrows candidates with plain LIKE conditions,
 * then PHP ranks them with a Levenshtein-based score (see score()).
 */
final class NameIndex
{
    /** Lowercase, ASCII-fold (ñ becomes n), keep only letters and single spaces. */
    public static function normalize(?string $value): string
    {
        $ascii = Str::lower(Str::ascii((string) $value));
        $clean = preg_replace('/[^a-z ]+/', ' ', $ascii);

        return trim(preg_replace('/\s+/', ' ', $clean));
    }

    /** @return list<string> */
    public static function tokens(?string $value): array
    {
        $normalized = self::normalize($value);

        return $normalized === '' ? [] : explode(' ', $normalized);
    }

    /** Searchable key: "last first middle suffix", normalized. */
    public static function key(string ...$parts): string
    {
        return self::normalize(implode(' ', $parts));
    }

    /** Space separated phonetic codes of every name token. */
    public static function metaphone(string $key): string
    {
        $codes = array_map(
            fn (string $token) => metaphone($token),
            $key === '' ? [] : explode(' ', $key),
        );

        return implode(' ', array_filter($codes));
    }

    /**
     * Similarity between a search phrase and a name, from 0.0 to 1.0.
     * Every query token must find its best match among the name tokens;
     * the score is the average of those best matches, so word order is ignored
     * ("cruz juan" matches "Juan Dela Cruz").
     *
     * @param  list<string>  $queryTokens
     * @param  list<string>  $nameTokens
     */
    public static function score(array $queryTokens, array $nameTokens): float
    {
        if ($queryTokens === [] || $nameTokens === []) {
            return 0.0;
        }

        // "delacruz" must match "dela cruz": also compare against adjacent tokens joined together.
        $count = count($nameTokens);
        for ($i = 0; $i < $count - 1; $i++) {
            $nameTokens[] = $nameTokens[$i].$nameTokens[$i + 1];
        }

        $total = 0.0;
        foreach ($queryTokens as $query) {
            $best = 0.0;
            foreach ($nameTokens as $name) {
                if (str_starts_with($name, $query)) {
                    $best = max($best, 0.9 + 0.1 * (strlen($query) / strlen($name)));

                    continue;
                }
                $longest = max(strlen($query), strlen($name));
                $best = max($best, 1 - (levenshtein($query, $name) / $longest));
            }
            $total += $best;
        }

        return $total / count($queryTokens);
    }
}
