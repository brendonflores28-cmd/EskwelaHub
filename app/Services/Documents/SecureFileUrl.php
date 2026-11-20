<?php

namespace App\Services\Documents;

use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\URL;

/**
 * Files are never reachable by a plain URL. The only way to get one is a
 * signed link that works for two minutes and still requires a login.
 */
class SecureFileUrl
{
    public const LIFETIME_MINUTES = 2;

    public function for(GeneratedDocument $document): string
    {
        return URL::temporarySignedRoute(
            'documents.file',
            now()->addMinutes(self::LIFETIME_MINUTES),
            ['document' => $document->id],
        );
    }
}
