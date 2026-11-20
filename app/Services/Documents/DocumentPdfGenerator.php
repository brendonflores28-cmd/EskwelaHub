<?php

namespace App\Services\Documents;

use App\Models\DocumentRequest;
use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** Builds the PDF of a request and stores it on the private disk. */
class DocumentPdfGenerator
{
    public const DISK = 'private';

    public function __construct(private readonly QrCodeService $qr) {}

    public function generate(DocumentRequest $request): GeneratedDocument
    {
        $request->loadMissing([
            'student.enrollments.schoolYear',
            'student.enrollments.section',
            'student.graduation.schoolYear',
        ]);

        $binary = Pdf::loadView('pdf.document', [
            'request' => $request,
            'student' => $request->student,
            'qr' => $this->qr->dataUri(route('verify', $request->verification_token)),
            'school' => config('eskwelahub.school_name'),
            'location' => config('eskwelahub.school_location'),
            'issuedAt' => now(),
        ])->setPaper('a4')->output();

        // documents/<request id>/<request no>-<timestamp>.pdf, kept outside public/
        $path = sprintf('documents/%d/%s-%s.pdf', $request->id, $request->request_no, now()->format('YmdHis'));
        Storage::disk(self::DISK)->put($path, $binary);

        return $request->document()->create([
            'disk_path' => $path,
            'sha256' => hash('sha256', $binary),
            'size' => strlen($binary),
            'generated_at' => now(),
        ]);
    }
}
