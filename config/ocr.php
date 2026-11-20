<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR engine
    |--------------------------------------------------------------------------
    | browser   : Tesseract.js runs in the staff member's browser (default, private).
    | ocr_space : the server sends the scan to the OCR.space API (third party).
    */
    'driver' => env('OCR_DRIVER', 'browser'),

    'ocr_space' => [
        'key' => env('OCR_SPACE_KEY'),
        'url' => env('OCR_SPACE_URL', 'https://api.ocr.space/parse/image'),
    ],

    // Allowed scan file types and size (kilobytes).
    'max_kb' => 8192,
    'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
];
