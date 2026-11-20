<?php

namespace App\Contracts;

use App\Services\Sentiment\SentimentResult;

/**
 * Labels a survey comment as positive, neutral or negative.
 *
 * The first implementation (LexiconSentimentAnalyzer) uses word lists in
 * English and Tagalog and needs no external service. To use an AI API later,
 * write another class that implements this interface and bind it in
 * AppServiceProvider (see config/sentiment.php).
 */
interface SentimentAnalyzer
{
    public function analyze(string $text): SentimentResult;
}
