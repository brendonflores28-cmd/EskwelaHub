<?php

namespace App\Services\Chatbot;

use App\Contracts\ChatbotResponder;
use App\Models\FaqEntry;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Optional answerer that uses a language model (Anthropic Messages API) so people can
 * write in any words, in English, Tagalog or both, with spelling mistakes, and still
 * get a polite answer. Set CHATBOT_DRIVER=llm and ANTHROPIC_API_KEY to turn it on.
 *
 * Only the typed question and the public FAQ text leave the server: no user name,
 * email, LRN or record. Without a key, or when the service fails, the rule based
 * FaqChatbot answers instead, so the assistant never goes silent.
 */
class LlmChatbot implements ChatbotResponder
{
    public function __construct(private readonly FaqChatbot $fallback, private readonly GuideCatalog $guides) {}

    public function respond(string $question): ChatReply
    {
        $key = config('chatbot.llm.key');
        if (! $key) {
            return $this->fallback->respond($question);
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
                ->timeout(15)
                ->post(config('chatbot.llm.url'), [
                    'model' => config('chatbot.llm.model'),
                    'max_tokens' => 500,
                    'system' => $this->system(),
                    'messages' => [['role' => 'user', 'content' => $question]],
                ]);

            $data = $response->successful() ? $this->parse((string) $response->json('content.0.text')) : null;
            if ($data === null) {
                return $this->fallback->respond($question);
            }

            $guide = in_array($data['guide'] ?? null, $this->guides->ids(), true) ? $data['guide'] : null;

            return new ChatReply(
                answer: trim((string) $data['answer']),
                lang: ($data['lang'] ?? '') === 'tl' ? 'tl' : 'en',
                guide: $guide,
                model: true,
            );
        } catch (Throwable) {
            return $this->fallback->respond($question);
        }
    }

    /** @return array{answer: string, guide?: string|null, lang?: string}|null */
    private function parse(string $text): ?array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text) ?? '');
        $data = json_decode($text, true);

        return is_array($data) && filled($data['answer'] ?? null) ? $data : null;
    }

    private function system(): string
    {
        $faq = FaqEntry::query()->where('is_active', true)->orderBy('sort')->get()
            ->map(fn (FaqEntry $e) => '- '.$e->question.' => '.$e->answer)
            ->implode("\n");

        $guides = collect($this->guides->describe())->map(fn (string $line) => '- '.$line)->implode("\n");

        return <<<PROMPT
You are the help assistant of EskwelaHub, the school portal of Cawayan National High School (Bongabong, Oriental Mindoro).
Users are learners, parents, alumni, teachers, the registrar and the principal.

Rules:
- Reply in the user's language: Tagalog if they wrote Tagalog or Taglish, otherwise English. Accept misspellings and short or messy sentences when you can understand them.
- Be polite, warm and short (at most 3 sentences). Use "po" naturally in Tagalog.
- Answer portal questions only from the FAQ below. Never invent fees, dates, office hours, names or policies; say the registrar's office knows.
- If the question is not about the school or the portal, answer politely in one short sentence if it is harmless, then offer help with enrollment, documents or the portal. Never give medical, legal or financial advice.
- Never ask for passwords, LRNs or personal details.
- If a guide below fits what the user wants to do or where to click, put its id in "guide". Otherwise use null.

Guides:
{$guides}

FAQ:
{$faq}

Answer with JSON only, no other text: {"answer": "...", "guide": "id or null", "lang": "en or tl"}
PROMPT;
    }
}
