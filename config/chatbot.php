<?php

return [

    // faq: rule based, answers come from the faq_entries table. Add "llm" later and bind it in AppServiceProvider.
    'driver' => env('CHATBOT_DRIVER', 'faq'),

    // Minimum match (0 to 1) before the bot answers instead of suggesting questions.
    'min_score' => 0.5,

    'fallback' => 'I am not sure about that. Please choose one of the questions below, or ask the registrar\'s office.',
];
