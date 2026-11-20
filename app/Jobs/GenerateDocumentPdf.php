<?php

namespace App\Jobs;

use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use App\Services\Documents\DocumentPdfGenerator;
use App\Services\Requests\RequestWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Creates the PDF for an approved request: approved -> processing -> ready.
 * Runs from the database queue (drained every minute by the scheduler).
 */
class GenerateDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * The "one job per request" lock expires after 5 minutes. Without this, a worker that crashed
     * (or a cache that was not cleared) would block the request for good: the lock never expired.
     */
    public int $uniqueFor = 300;

    public function __construct(public int $requestId) {}

    /** One job per request at a time. */
    public function uniqueId(): string
    {
        return (string) $this->requestId;
    }

    public function handle(RequestWorkflow $workflow, DocumentPdfGenerator $generator): void
    {
        $request = DocumentRequest::findOrFail($this->requestId);

        if ($request->status === RequestStatus::Approved) {
            $workflow->markProcessing($request);
            $request->refresh();
        }

        // Anything else (ready, released, rejected) means the work is already done or not wanted.
        if ($request->status !== RequestStatus::Processing) {
            return;
        }

        $generator->generate($request);
        $workflow->markReady($request);
    }
}
