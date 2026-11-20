<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\GeneratedDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\DocumentPdfGenerator;
use App\Services\Documents\SecureFileUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Two steps, both logged and both needing a login:
 *  1. request(): checks the policy and redirects to a short-lived signed link;
 *  2. file(): checks the signature and the policy again, then streams the PDF.
 * The PDF itself sits in storage/app/private and has no public URL.
 */
class DocumentDownloadController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function request(DocumentRequest $documentRequest, SecureFileUrl $urls): RedirectResponse
    {
        $this->authorize('download', $documentRequest);

        $document = $documentRequest->document;
        abort_if($document === null, 404);

        return redirect()->to($urls->for($document));
    }

    public function file(GeneratedDocument $document): StreamedResponse
    {
        $documentRequest = $document->request;
        $this->authorize('download', $documentRequest);

        $this->audit->record($documentRequest, 'downloaded', 'Downloaded '.$documentRequest->request_no);

        return Storage::disk(DocumentPdfGenerator::DISK)->download(
            $document->disk_path,
            $documentRequest->request_no.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
