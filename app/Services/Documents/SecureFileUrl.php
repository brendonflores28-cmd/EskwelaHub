<?php

namespace App\Services\Documents;

use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\URL;

/**
 * Files are never reachable by a plain URL. The only way to get one is a
 * signed link that expires quickly and still requires a login.
 */
class SecureFileUrl
{
    public const LIFETIME_MINUTES = 2;

    /** Link to a generated PDF (valid 2 minutes). */
    public function for(GeneratedDocument $document): string
    {
        return $this->signed('documents.file', ['document' => $document->id]);
    }

    /**
     * Signed link to any named route. Use a longer lifetime only for images
     * shown inside a page the user keeps open (for example a scan under review).
     *
     * @param  array<string, mixed>  $parameters
     */
    public function signed(string $route, array $parameters, int $minutes = self::LIFETIME_MINUTES): string
    {
        return URL::temporarySignedRoute($route, now()->addMinutes($minutes), $parameters);
    }
}
