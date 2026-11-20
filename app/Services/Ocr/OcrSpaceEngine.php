<?php

namespace App\Services\Ocr;

use App\Contracts\OcrEngine;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Optional server engine using the OCR.space web API (free tier).
 * PRIVACY: the scan is sent to a third party. Use it only for records you are
 * allowed to share, and only after the school agreed to it (RA 10173).
 */
class OcrSpaceEngine implements OcrEngine
{
    public function name(): string
    {
        return 'ocr_space';
    }

    public function runsOnServer(): bool
    {
        return true;
    }

    public function extract(string $absolutePath, string $mime): ?string
    {
        $key = config('ocr.ocr_space.key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders(['apikey' => $key])
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
                ->post(config('ocr.ocr_space.url'), ['language' => 'eng', 'OCREngine' => 2]);
        } catch (Throwable) {
            return null; // network problem: staff can type the record by hand
        }

        if (! $response->successful() || $response->json('IsErroredOnProcessing')) {
            return null;
        }

        $text = collect($response->json('ParsedResults', []))->pluck('ParsedText')->implode("\n");

        return $text !== '' ? $text : null;
    }
}
