<?php

namespace App\Providers;

use App\Contracts\ChatbotResponder;
use App\Contracts\OcrEngine;
use App\Contracts\SentimentAnalyzer;
use App\Listeners\RecordSessionEvent;
use App\Services\Chatbot\FaqChatbot;
use App\Services\Ocr\BrowserOcrEngine;
use App\Services\Ocr\OcrSpaceEngine;
use App\Services\Sentiment\LexiconSentimentAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The OCR engine is chosen by OCR_DRIVER in .env (see config/ocr.php).
        // Swappable parts: replace these bindings to use an AI API (see config/sentiment.php, config/chatbot.php).
        $this->app->bind(SentimentAnalyzer::class, LexiconSentimentAnalyzer::class);
        $this->app->bind(ChatbotResponder::class, FaqChatbot::class);

        $this->app->bind(OcrEngine::class, fn () => match (config('ocr.driver')) {
            'ocr_space' => new OcrSpaceEngine,
            default => new BrowserOcrEngine,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->recordSessionEvents();
    }

    /**
     * Sign-in events go to the session log (separate from the audit trail,
     * which records what users do inside the system).
     */
    protected function recordSessionEvents(): void
    {
        foreach ([
            Login::class, Logout::class, Failed::class, Lockout::class, Registered::class, Verified::class,
            PasswordReset::class, PasswordUpdatedViaController::class, TwoFactorAuthenticationEnabled::class,
            TwoFactorAuthenticationDisabled::class, TwoFactorAuthenticationChallenged::class, TwoFactorAuthenticationFailed::class,
        ] as $event) {
            Event::listen($event, RecordSessionEvent::class);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
