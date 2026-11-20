<?php

namespace App\Services\Ocr;

use App\Contracts\OcrEngine;

/** Default: recognition already happened in the browser (Tesseract.js). Nothing leaves the school's server. */
class BrowserOcrEngine implements OcrEngine
{
    public function name(): string
    {
        return 'browser';
    }

    public function runsOnServer(): bool
    {
        return false;
    }

    public function extract(string $absolutePath, string $mime): ?string
    {
        return null; // the text comes from the browser, see OcrController::store()
    }
}
