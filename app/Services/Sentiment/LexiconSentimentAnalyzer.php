<?php

namespace App\Services\Sentiment;

use App\Contracts\SentimentAnalyzer;
use App\Enums\Sentiment;
use Illuminate\Support\Str;

/**
 * Word-list sentiment for English and Tagalog (and mixed "Taglish") comments.
 *
 * Each known word adds its weight (positive) or subtracts it (negative).
 * A negator within the two words before it flips the sign ("hindi maganda",
 * "not good", "walang problema"). An intensifier right before it makes it
 * stronger ("sobrang bagal", "very good"). Negation never crosses a comma or
 * full stop. The label depends on the sum over the whole comment.
 */
class LexiconSentimentAnalyzer implements SentimentAnalyzer
{
    /** @var array{positive: array<string, int>, negative: array<string, int>, negators: list<string>, intensifiers: list<string>}|null */
    private ?array $lexicon = null;

    public function analyze(string $text): SentimentResult
    {
        $score = 0.0;

        foreach ($this->clauses($text) as $tokens) {
            $score += $this->scoreClause($tokens);
        }

        $threshold = (float) config('sentiment.threshold');
        $label = match (true) {
            $score >= $threshold => Sentiment::Positive,
            $score <= -$threshold => Sentiment::Negative,
            default => Sentiment::Neutral,
        };

        return new SentimentResult($label, round($score, 2));
    }

    /** @param  list<string>  $tokens words of one clause */
    private function scoreClause(array $tokens): float
    {
        $lexicon = $this->lexicon();
        $score = 0.0;

        foreach ($tokens as $i => $token) {
            $value = ($lexicon['positive'][$token] ?? 0) - ($lexicon['negative'][$token] ?? 0);
            if ($value === 0) {
                continue;
            }

            if (isset($tokens[$i - 1]) && in_array($tokens[$i - 1], $lexicon['intensifiers'], true)) {
                $value *= 1.5;
            }

            $negated = (isset($tokens[$i - 1]) && in_array($tokens[$i - 1], $lexicon['negators'], true))
                || (isset($tokens[$i - 2]) && in_array($tokens[$i - 2], $lexicon['negators'], true));
            if ($negated) {
                $value *= -0.8; // "not good" is bad, but a little less than "bad"
            }

            $score += $value;
        }

        return $score;
    }

    /**
     * Words grouped by clause (split at punctuation). Lowercase, accents
     * folded, apostrophes removed so "don't" becomes "dont".
     *
     * @return list<list<string>>
     */
    private function clauses(string $text): array
    {
        $clean = str_replace(["'", '’'], '', Str::lower(Str::ascii($text)));

        return collect(preg_split('/[.,;:!?\n]+/', $clean) ?: [])
            ->map(fn (string $clause) => preg_split('/[^a-z]+/', $clause, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter()
            ->values()
            ->all();
    }

    /** English and Tagalog lists merged into one. */
    private function lexicon(): array
    {
        return $this->lexicon ??= (function (): array {
            $en = require resource_path('lexicon/en.php');
            $tl = require resource_path('lexicon/tl.php');

            return [
                'positive' => $en['positive'] + $tl['positive'],
                'negative' => $en['negative'] + $tl['negative'],
                'negators' => array_merge($en['negators'], $tl['negators']),
                'intensifiers' => array_merge($en['intensifiers'], $tl['intensifiers']),
            ];
        })();
    }
}
