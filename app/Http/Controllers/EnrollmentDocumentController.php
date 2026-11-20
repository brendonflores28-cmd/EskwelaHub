<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\SecureFileUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uploaded enrollment documents (birth certificate, report card...).
 * Same two steps as generated PDFs: policy check, then a 2-minute signed link
 * that is checked again before the file is streamed. Every open is logged.
 */
class EnrollmentDocumentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function request(EnrollmentDocument $document, SecureFileUrl $urls): RedirectResponse
    {
        $this->authorize('view', $document->application);

        return redirect()->to($urls->signed('enrollment-documents.file', ['document' => $document->id]));
    }

    public function file(EnrollmentDocument $document): StreamedResponse
    {
        $this->authorize('view', $document->application);

        $this->audit->record($document->application, 'document_opened', "Opened {$document->kind} of {$document->application->reference_no}");

        return Storage::disk('private')->response($document->disk_path, $document->original_name, [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
