<?php

namespace App\Services\Chatbot;

use App\Contracts\ChatbotResponder;
use App\Models\FaqEntry;
use App\Support\NameIndex;

/**
 * Rule based FAQ bot. No AI and no external service.
 *
 * The visitor's words (minus filler words like "ang", "the", "po") are
 * compared with the words of each FAQ entry (its question and keywords).
 * Exact words count fully, close spellings (one typo, or the same start) count
 * less. The best entry answers when its score is high enough; otherwise the
 * bot offers the closest questions.
 */
class FaqChatbot implements ChatbotResponder
{
    /** Filler words in English and Tagalog that say nothing about the topic. */
    private const STOPWORDS = [
        'a', 'an', 'the', 'is', 'are', 'am', 'do', 'does', 'i', 'we', 'you', 'to', 'of', 'in', 'on', 'for', 'and', 'or',
        'my', 'me', 'can', 'how', 'what', 'when', 'where', 'who', 'please', 'hello', 'hi', 'ba', 'po', 'opo', 'ang',
        'ng', 'mga', 'sa', 'na', 'ko', 'ako', 'namin', 'ano', 'paano', 'saan', 'kailan', 'pwede', 'puwede', 'may',
        'at', 'o', 'kung', 'para', 'ba', 'yung', 'ung', 'lang', 'din', 'rin', 'naman', 'need', 'want', 'get',
    ];

    public function respond(string $question): ChatReply
    {
        $words = $this->words($question);
        $entries = FaqEntry::query()->where('is_active', true)->orderBy('sort')->get();

        if ($words === [] || $entries->isEmpty()) {
            return new ChatReply((string) config('chatbot.fallback'), null, $this->suggest($entries->all()));
        }

        // Rare words say more than common ones ("transferee" more than "need"): weight each query word
        // by how few entries contain it.
        $entryWords = $entries->map(fn (FaqEntry $e) => $this->words($e->question.' '.$e->keywords))->all();
        $weights = [];
        foreach ($words as $word) {
            $df = count(array_filter($entryWords, fn (array $list) => in_array($word, $list, true)));
            $weights[$word] = $df === 0 ? 1.0 : log(1 + count($entryWords) / $df);
        }

        $scored = $entries
            ->values()
            ->map(fn (FaqEntry $e, int $i) => ['entry' => $e, 'score' => $this->score($words, $entryWords[$i], $weights)])
            ->sortByDesc('score')
            ->values();

        $best = $scored->first();
        if ($best['score'] >= (float) config('chatbot.min_score')) {
            return new ChatReply($best['entry']->answer, $best['entry']->id);
        }

        // Not sure: offer the closest questions (or the first few when nothing is close).
        $close = $scored->filter(fn (array $row) => $row['score'] > 0.2)->take(3)->pluck('entry')->all();

        return new ChatReply((string) config('chatbot.fallback'), null, $this->suggest($close ?: $entries->take(4)->all()));
    }

    /**
     * Average over the visitor's words of how well each one is found in the entry.
     *
     * @param  list<string>  $query
     * @param  list<string>  $entryWords
     * @param  array<string, float>  $weights  importance of each query word
     */
    private function score(array $query, array $entryWords, array $weights): float
    {
        $total = 0.0;
        $weightSum = 0.0;

        foreach ($query as $word) {
            $best = 0.0;
            foreach ($entryWords as $candidate) {
                if ($candidate === $word) {
                    $best = 1.0;
                    break;
                }
                if (strlen($word) >= 4 && strlen($candidate) >= 4 && (str_starts_with($candidate, $word) || str_starts_with($word, $candidate))) {
                    $best = max($best, 0.8);
                } elseif (strlen($word) >= 5 && levenshtein($word, $candidate) <= 1) {
                    $best = max($best, 0.7); // one typo
                }
            }
            $total += $best * $weights[$word];
            $weightSum += $weights[$word];
        }

        return $total / $weightSum;
    }

    /** @return list<string> */
    private function words(string $text): array
    {
        return array_values(array_filter(
            NameIndex::tokens($text),
            fn (string $w) => strlen($w) > 1 && ! in_array($w, self::STOPWORDS, true),
        ));
    }

    /**
     * @param  array<int, FaqEntry>  $entries
     * @return list<array{id: int, question: string}>
     */
    private function suggest(array $entries): array
    {
        return array_values(array_map(fn (FaqEntry $e) => ['id' => $e->id, 'question' => $e->question], $entries));
    }
}
