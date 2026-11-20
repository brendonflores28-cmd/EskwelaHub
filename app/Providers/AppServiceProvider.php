<?php

namespace App\Providers;

use App\Contracts\OcrEngine;
use App\Services\Ocr\BrowserOcrEngine;
use App\Services\Ocr\OcrSpaceEngine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The OCR engine is chosen by OCR_DRIVER in .env (see config/ocr.php).
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
