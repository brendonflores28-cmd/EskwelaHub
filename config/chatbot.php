<?php

return [

    // faq: rule based, answers come from the faq_entries table (works offline, free).
    // llm: a language model answers in any wording and language; it falls back to faq when it is off or fails.
    'driver' => env('CHATBOT_DRIVER', 'faq'),

    // Minimum match (0 to 1) before the bot answers instead of suggesting questions.
    'min_score' => 0.5,

    // Used by the llm driver. The key stays on the server; only the typed question and the public FAQ text are sent.
    'llm' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('CHATBOT_LLM_MODEL', 'claude-haiku-5-5'),
        'url' => 'https://api.anthropic.com/v1/messages',
    ],

    // Polite replies when the question is not about the portal, or not understood. en = English, tl = Tagalog.
    'fallback' => [
        'en' => 'I am not sure about that, and I may not know it because I only help with this school portal. Please choose one of the questions below, or ask the registrar\'s office.',
        'tl' => 'Pasensya na, hindi ako sigurado diyan. Tungkol lang sa portal ng school ang alam ko. Pumili sa mga tanong sa ibaba o magtanong sa opisina ng registrar.',
    ],

    'small_talk' => [
        'greet' => [
            'en' => 'Hello! I can help with enrollment, document requests and using this portal. What would you like to know?',
            'tl' => 'Kumusta po! Matutulungan kita sa enrollment, document requests at paggamit ng portal. Ano ang gusto mong malaman?',
        ],
        'thanks' => [
            'en' => 'You are welcome! Ask me anytime.',
            'tl' => 'Walang anuman po! Magtanong ka lang anumang oras.',
        ],
        'bye' => [
            'en' => 'Goodbye! Come back if you need help.',
            'tl' => 'Paalam po! Bumalik ka lang kung kailangan mo ng tulong.',
        ],
        'who' => [
            'en' => 'I am the EskwelaHub assistant of Cawayan National High School. I answer questions in English or Tagalog and can show you where to click.',
            'tl' => 'Ako ang EskwelaHub assistant ng Cawayan National High School. Sumasagot ako sa English o Tagalog at maipapakita ko kung saan magki-click.',
        ],
    ],
];
