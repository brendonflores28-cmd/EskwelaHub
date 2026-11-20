<?php

namespace App\Contracts;

use App\Services\Chatbot\ChatReply;

/**
 * Answers a visitor's question about enrollment and document requirements.
 *
 * FaqChatbot is rule based: it matches words against the FAQ table. To use a
 * language model later, write another class that implements this interface
 * and bind it in AppServiceProvider (config/chatbot.php). The controller and
 * the page do not change.
 */
interface ChatbotResponder
{
    public function respond(string $question): ChatReply;
}
