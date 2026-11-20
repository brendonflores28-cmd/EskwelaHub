<?php

use App\Contracts\ChatbotResponder;
use App\Models\ChatLog;
use App\Models\FaqEntry;
use App\Services\Chatbot\ChatReply;
use App\Services\Chatbot\FaqChatbot;
use Database\Seeders\FaqSeeder;

beforeEach(fn () => $this->seed(FaqSeeder::class));

function ask($test, string $question)
{
    return $test->postJson(route('chatbot.ask'), ['question' => $question]);
}

it('answers in English', function () {
    ask($this, 'How do I request Form 137?')
        ->assertOk()
        ->assertJsonPath('matched', true)
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'My requests'));

    ask($this, 'What documents do I need to enroll my child in Grade 7?')
        ->assertJsonPath('matched', true)
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'PSA birth certificate'));
});

it('answers in Tagalog and Taglish', function () {
    ask($this, 'Paano mag-request ng form 137?')->assertJsonPath('matched', true)
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Form 137'));

    ask($this, 'ano ang kailangan para sa transferee')->assertJsonPath('matched', true)
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'good moral'));

    ask($this, 'nakalimutan ko password ko')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Forgot password'));
});

it('forgives small typos', function () {
    ask($this, 'how to download my docment pdf')->assertJsonPath('matched', true)
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Download PDF'));
});

it('suggests questions when it does not know', function () {
    $response = ask($this, 'qwerty zxcv asdf')->assertOk()->assertJsonPath('matched', false);

    expect($response->json('suggestions'))->not->toBeEmpty()
        ->and($response->json('answer'))->toContain('not sure');
});

it('logs every question and marks the ones it could not answer', function () {
    ask($this, 'How do I request Form 137?');
    ask($this, 'qwerty zxcv asdf');

    expect(ChatLog::count())->toBe(2)
        ->and(ChatLog::where('matched', true)->count())->toBe(1)
        ->and(ChatLog::where('matched', false)->count())->toBe(1);
});

it('validates the question', function () {
    ask($this, '')->assertStatus(422);
    ask($this, str_repeat('a', 301))->assertStatus(422);
});

it('ignores inactive FAQ entries', function () {
    FaqEntry::query()->update(['is_active' => false]);

    ask($this, 'How do I request Form 137?')->assertJsonPath('matched', false);
});

it('is rate limited', function () {
    foreach (range(1, 20) as $i) {
        ask($this, 'form 137')->assertOk();
    }

    ask($this, 'form 137')->assertStatus(429);
});

it('shows the public help page to guests with the FAQ list', function () {
    $this->get(route('help'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/Help')->has('faqs', FaqEntry::count()));
});

it('can be replaced through the interface (for example with a language model)', function () {
    app()->bind(ChatbotResponder::class, fn () => new class implements ChatbotResponder
    {
        public function respond(string $question): ChatReply
        {
            return new ChatReply('From another engine', FaqEntry::query()->value('id'));
        }
    });

    ask($this, 'anything')->assertJsonPath('answer', 'From another engine');
    expect(app(FaqChatbot::class))->toBeInstanceOf(FaqChatbot::class);
});
