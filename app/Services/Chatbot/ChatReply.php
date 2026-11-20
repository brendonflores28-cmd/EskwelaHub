<?php

namespace App\Services\Chatbot;

/**
 * @param  list<array{id: int, question: string}>  $suggestions  related questions when no answer matched well
 * @param  string  $lang  'en' or 'tl', the language of the answer
 * @param  string|null  $guide  id of a step-by-step guide (config/guides.php) that teaches this answer
 * @param  bool  $model  true when a language model wrote the answer
 */
final readonly class ChatReply
{
    public function __construct(
        public string $answer,
        public ?int $faqId = null,
        public array $suggestions = [],
        public string $lang = 'en',
        public ?string $guide = null,
        public bool $model = false,
    ) {}

    public function matched(): bool
    {
        return $this->faqId !== null || $this->model;
    }
}
