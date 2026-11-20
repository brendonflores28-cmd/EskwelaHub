<?php

namespace App\Services\Chatbot;

/**
 * @param  list<array{id: int, question: string}>  $suggestions  related questions when no answer matched well
 */
final readonly class ChatReply
{
    public function __construct(
        public string $answer,
        public ?int $faqId = null,
        public array $suggestions = [],
    ) {}

    public function matched(): bool
    {
        return $this->faqId !== null;
    }
}
