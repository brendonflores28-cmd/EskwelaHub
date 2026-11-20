<?php

namespace App\Services\Chatbot;

use App\Contracts\ChatbotResponder;
use App\Models\FaqEntry;
use App\Support\NameIndex;

/**
 * Rule based FAQ bot. No AI and no external service.
 *
 * The visitor's words (minus filler words like "ang", "the", "po") are
 * compared with the words of each FAQ entry (its question, Tagalog question and
 * keywords). Exact words count fully, close spellings (typos, the same start)
 * count less. The best entry answers, in the language the visitor wrote, when
 * its score is high enough; otherwise the bot is polite, says what it can help
 * with and offers the closest questions.
 */
class FaqChatbot implements ChatbotResponder
{
    /** Filler words in English and Tagalog that say nothing about the topic. */
    private const STOPWORDS = [
        'a', 'an', 'the', 'is', 'are', 'am', 'do', 'does', 'i', 'we', 'you', 'to', 'of', 'in', 'on', 'for', 'and', 'or',
        'my', 'me', 'can', 'how', 'what', 'when', 'where', 'who', 'please', 'hello', 'hi', 'ba', 'po', 'opo', 'ang',
        'ng', 'mga', 'sa', 'na', 'ko', 'ako', 'namin', 'ano', 'paano', 'saan', 'kailan', 'pwede', 'puwede', 'may',
        'at', 'o', 'kung', 'para', 'yung', 'ung', 'lang', 'din', 'rin', 'naman', 'need', 'want', 'get',
        'pede', 'yan', 'ito', 'iyan', 'mo', 'gusto', 'tulong', 'paki', 'turuan', 'ipakita', 'ituro', 'mag', 'nag', 'pag',
        'show', 'teach', 'help', 'tell', 'guide', 'steps', 'tutorial', 'tulungan', 'pano', 'ni', 'nang',
    ];

    public function respond(string $question): ChatReply
    {
        $lang = Language::detect($question);
        $words = $this->words($question);
        $entries = FaqEntry::query()->where('is_active', true)->orderBy('sort')->get();

        $scored = collect();
        if ($words !== [] && $entries->isNotEmpty()) {
            // Rare words say more than common ones ("transferee" more than "need"): weight each query word
            // by how few entries contain it.
            $entryWords = $entries->map(fn (FaqEntry $e) => $this->words($e->question.' '.$e->question_tl.' '.$e->keywords))->all();
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
                /** @var FaqEntry $entry */
                $entry = $best['entry'];

                return new ChatReply(
                    $lang === 'tl' && $entry->answer_tl ? $entry->answer_tl : $entry->answer,
                    $entry->id,
                    [],
                    $lang,
                    $entry->guide,
                );
            }
        }

        // Not a portal question: greet, thank, say goodbye politely, or say what the assistant can do.
        $close = $scored->filter(fn (array $row) => $row['score'] > 0.2)->take(3)->pluck('entry')->all();
        $suggestions = $this->suggest($close ?: $entries->take(4)->all(), $lang);

        $talk = $this->smallTalk($question);
        if ($talk !== null) {
            return new ChatReply((string) config("chatbot.small_talk.{$talk}.{$lang}"), null, in_array($talk, ['thanks', 'bye'], true) ? [] : $suggestions, $lang);
        }

        return new ChatReply((string) config("chatbot.fallback.{$lang}"), null, $suggestions, $lang);
    }

    /** Greeting, thanks, goodbye or "who are you", in English or Tagalog, typos allowed. */
    private function smallTalk(string $question): ?string
    {
        $words = NameIndex::tokens($question);
        if ($words === [] || count($words) > 6) {
            return null;
        }

        $has = fn (array $list): bool => array_filter($words, fn (string $w) => Language::like($w, $list)) !== [];

        return match (true) {
            $has(['thanks', 'thank', 'thankyou', 'salamat', 'tenkyu']) => 'thanks',
            $has(['bye', 'goodbye', 'paalam', 'ingat']) => 'bye',
            $has(['who', 'what', 'sino', 'ano']) && $has(['you', 'ikaw', 'ka', 'u']) => 'who',
            $has(['hi', 'hello', 'hey', 'hola', 'kumusta', 'musta', 'magandang', 'morning', 'afternoon', 'evening', 'umaga', 'hapon', 'gabi']) => 'greet',
            default => null,
        };
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
                } elseif (strlen($word) >= 7 && levenshtein($word, $candidate) <= 2) {
                    $best = max($best, 0.6); // two typos in a long word
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
    private function suggest(array $entries, string $lang = 'en'): array
    {
        return array_values(array_map(
            fn (FaqEntry $e) => ['id' => $e->id, 'question' => $lang === 'tl' && $e->question_tl ? $e->question_tl : $e->question],
            $entries,
        ));
    }
}
