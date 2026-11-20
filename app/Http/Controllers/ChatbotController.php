<?php

namespace App\Http\Controllers;

use App\Contracts\ChatbotResponder;
use App\Models\ChatLog;
use App\Models\FaqEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Public help page with the FAQ chatbot. No login needed, so it asks for no personal data. */
class ChatbotController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('public/Help', [
            'faqs' => FaqEntry::query()->where('is_active', true)->orderBy('sort')->get(['id', 'category', 'question', 'answer']),
        ]);
    }

    public function ask(Request $request, ChatbotResponder $bot): JsonResponse
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
        ]);
    }
}
