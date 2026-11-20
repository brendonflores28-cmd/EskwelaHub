<?php

use App\Contracts\ChatbotResponder;
use App\Enums\Role;
use App\Models\ChatLog;
use App\Models\FaqEntry;
use App\Models\User;
use App\Services\Chatbot\ChatReply;
use App\Services\Chatbot\FaqChatbot;
use Database\Seeders\FaqSeeder;
use Illuminate\Support\Facades\Http;

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

it('answers in the language of the question', function () {
    ask($this, 'paano mag-request ng form 137')
        ->assertJsonPath('lang', 'tl')
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'buksan ang "My requests"'));

    ask($this, 'how do I request form 137')->assertJsonPath('lang', 'en');
});

it('understands misspelled English and Tagalog', function () {
    ask($this, 'how to reqest form137 pls')->assertJsonPath('matched', true);
    ask($this, 'pano mag enrol sa grade 7')->assertJsonPath('matched', true)
        ->assertJsonPath('lang', 'tl');
    ask($this, 'nakalimotan ko pasword')->assertJsonPath('matched', true);
});

it('is polite with greetings, thanks and off-topic questions', function () {
    ask($this, 'kumusta po')->assertJsonPath('lang', 'tl')
        ->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Kumusta po'));
    ask($this, 'salamat po')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Walang anuman'));
    ask($this, 'helllo')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Hello'));
    ask($this, 'sino ka')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'assistant'));

    $off = ask($this, 'what is the capital of France')->assertOk()->assertJsonPath('matched', false);
    expect($off->json('answer'))->toContain('only help with this school portal')
        ->and($off->json('suggestions'))->not->toBeEmpty();

    ask($this, 'ano ang paborito mong kulay')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'Pasensya na'));
});

it('sends a guide matching the role of the person', function () {
    // Visitor asking a signed-in guide is told to log in first.
    $visitor = ask($this, 'paano mag request ng form 137')->assertJsonPath('guide.id', 'request_document');
    expect($visitor->json('guide.steps'))->toHaveCount(1)
        ->and($visitor->json('guide.steps.0.target'))->toBe('home-login')
        ->and($visitor->json('teach'))->toBeTrue();

    $parent = User::factory()->create();
    $parent->assignRole(Role::StudentParent->value);
    $this->actingAs($parent);
    $steps = ask($this, 'how do I request form 137')->json('guide.steps');
    expect($steps)->toHaveCount(2)->and($steps[0]['target'])->toBe('new-request-btn');

    // A parent never gets a staff guide.
    ask($this, 'how do I add a class section')->assertJsonPath('guide', null);

    $staff = User::factory()->create();
    $staff->assignRole(Role::Staff->value);
    $this->actingAs($staff);
    ask($this, 'how do I add a class section')->assertJsonPath('guide.id', 'add_section');
});
it('uses the language model when it is on, and falls back to the FAQ when it fails', function () {
    config(['chatbot.driver' => 'llm', 'chatbot.llm.key' => 'test-key']);

    $reply = fn (string $json) => ['content' => [['type' => 'text', 'text' => $json]]];
    Http::fake(['api.anthropic.com/*' => Http::sequence()
        ->push($reply('{"answer":"Pindutin ang New request po.","guide":"request_document","lang":"tl"}'))
        ->push($reply('{"answer":"Hi","guide":"hack_system","lang":"en"}'))
        ->push('error', 500)]);

    $this->actingAs(tap(User::factory()->create(), fn (User $u) => $u->assignRole(Role::StudentParent->value)));
    ask($this, 'pano humingi ng form 137')
        ->assertJsonPath('answer', 'Pindutin ang New request po.')
        ->assertJsonPath('matched', true)
        ->assertJsonPath('lang', 'tl')
        ->assertJsonPath('guide.id', 'request_document');

    // Never lets the model invent a guide id.
    ask($this, 'hello there')->assertJsonPath('guide', null);

    // Service down: the FAQ bot answers.
    ask($this, 'How do I request Form 137?')->assertJsonPath('answer', fn (string $a) => str_contains($a, 'My requests'));

    // No key: no call is made.
    config(['chatbot.llm.key' => null]);
    Http::fake();
    ask($this, 'How do I request Form 137?')->assertJsonPath('matched', true);
    Http::assertNothingSent();
});
