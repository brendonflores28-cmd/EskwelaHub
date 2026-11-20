<?php

namespace App\Services\Sentiment;

use App\Enums\Sentiment;

/** Label plus the raw score (positive above 0, negative below 0). */
final readonly class SentimentResult
{
    public function __construct(public Sentiment $label, public float $score) {}
}
