<?php

namespace App\Contracts;

/**
 * Reads text from a scanned image or PDF.
 *
 * Shared hosting cannot run Tesseract, so the default engine (BrowserOcrEngine)
 * lets the staff member's browser do the work with Tesseract.js and send the
 * text along with the file. A server engine (an OCR web API) can replace it by
 * changing OCR_DRIVER in .env. No controller code changes.
 */
interface OcrEngine
{
    /** Short id stored with each upload: "browser" or "ocr_space". */
    public function name(): string;

    /** True when the server itself reads the file; false when the browser sends the text. */
    public function runsOnServer(): bool;

    /**
     * Text found in the file, or null when it cannot be read.
     *
     * @param  string  $absolutePath  file on the private disk
     */
    public function extract(string $absolutePath, string $mime): ?string;
}
