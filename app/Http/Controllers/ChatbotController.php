<?php

namespace App\Http\Controllers;

use App\Contracts\ChatbotResponder;
use App\Models\ChatLog;
use App\Services\Chatbot\GuideCatalog;
use App\Services\Chatbot\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backend of the floating help assistant (resources/js/components/ChatWidget.vue).
 * Open to visitors who are not logged in, so it asks for no personal data.
 * When the answer has a step-by-step guide it is sent too, trimmed to what the
 * person's role may use; `teach` says they asked how or where, so it can start by itself.
 */
class ChatbotController extends Controller
{
    public function ask(Request $request, ChatbotResponder $bot, GuideCatalog $guides): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'min:2', 'max:300']]);

        $reply = $bot->respond($data['question']);

        ChatLog::create([
            'user_id' => $request->user()?->id,
            'question' => $data['question'],
            'faq_entry_id' => $reply->faqId,
            'matched' => $reply->matched(),
        ]);

        return response()->json([
            'answer' => $reply->answer,
            'matched' => $reply->matched(),
            'suggestions' => $reply->suggestions,
            'lang' => $reply->lang,
            'guide' => $guides->resolve($reply->guide, $request->user()),
            'teach' => Language::wantsTeaching($data['question']),
        ]);
    }
}
