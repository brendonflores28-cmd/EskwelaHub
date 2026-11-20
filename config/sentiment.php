<?php

return [

    // lexicon: word lists in resources/lexicon (English and Tagalog). Add "api" later and bind it in AppServiceProvider.
    'driver' => env('SENTIMENT_DRIVER', 'lexicon'),

    // The summed word score must reach this distance from zero to leave "neutral".
    'threshold' => 0.5,
];
