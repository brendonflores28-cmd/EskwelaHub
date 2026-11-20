<?php

use App\Contracts\SentimentAnalyzer;
use App\Enums\Sentiment;
use App\Services\Sentiment\LexiconSentimentAnalyzer;
use App\Services\Sentiment\SentimentResult;

dataset('comments', [
    'english positive' => ['The staff was very helpful and fast, thank you!', Sentiment::Positive],
    'english negative' => ['The process was slow and the staff was rude.', Sentiment::Negative],
    'tagalog positive' => ['Mabilis at maayos ang serbisyo, salamat po', Sentiment::Positive],
    'tagalog negative' => ['Sobrang bagal at pangit ng serbisyo', Sentiment::Negative],
    'taglish positive' => ['Ang bilis ng process, very helpful ang staff', Sentiment::Positive],
    'taglish negative' => ['Ang tagal ng release, nakakainis talaga', Sentiment::Negative],
    'negated positive' => ['hindi maganda ang service', Sentiment::Negative],
    'english negated positive' => ['not good at all', Sentiment::Negative],
    'negated negative' => ['walang problema, thank you', Sentiment::Positive],
    'not bad' => ['it was not bad', Sentiment::Positive],
    'neutral facts' => ['I submitted the form on Monday and came back on Friday', Sentiment::Neutral],
    'mixed cancels out' => ['mabilis pero mabagal', Sentiment::Neutral],
    'accents and caps' => ['EXCELLENT service!!!', Sentiment::Positive],
]);

it('labels comments in English, Tagalog and Taglish', function (string $comment, Sentiment $expected) {
    expect((new LexiconSentimentAnalyzer)->analyze($comment)->label)->toBe($expected);
})->with('comments');

it('makes an intensified word stronger', function () {
    $plain = (new LexiconSentimentAnalyzer)->analyze('bagal');
    $strong = (new LexiconSentimentAnalyzer)->analyze('sobrang bagal');

    expect($strong->score)->toBeLessThan($plain->score);
});

it('handles empty and symbol-only text as neutral', function () {
    expect((new LexiconSentimentAnalyzer)->analyze('')->label)->toBe(Sentiment::Neutral)
        ->and((new LexiconSentimentAnalyzer)->analyze('!!! ???')->label)->toBe(Sentiment::Neutral);
});

it('is the default analyzer and can be replaced through the interface', function () {
    expect(app(SentimentAnalyzer::class))->toBeInstanceOf(LexiconSentimentAnalyzer::class);

    app()->bind(SentimentAnalyzer::class, fn () => new class implements SentimentAnalyzer
    {
        public function analyze(string $text): SentimentResult
        {
            return new SentimentResult(Sentiment::Positive, 9.0);
        }
    });

    expect(app(SentimentAnalyzer::class)->analyze('anything')->score)->toBe(9.0);
});
