<?php

namespace App\Services\Documents;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/** Makes the QR code printed on every document. Uses GD, no extra server software. */
class QrCodeService
{
    /** PNG as a data URI, ready for an <img src> inside the PDF. */
    public function dataUri(string $text): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 6,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($text);
    }
}
