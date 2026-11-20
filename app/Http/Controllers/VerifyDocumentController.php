<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public page opened by the QR code. It only says whether the document is
 * genuine. The learner's name is shortened so the page leaks no personal data.
 */
class VerifyDocumentController extends Controller
{
    public function __invoke(string $token): Response
    {
        $request = DocumentRequest::query()
            ->with('student')
            ->where('verification_token', $token)
            ->first();

        $valid = $request !== null && in_array($request->status, [RequestStatus::Ready, RequestStatus::Released], true);

        return Inertia::render('public/Verify', [
            'valid' => $valid,
            'document' => $valid ? [
                'request_no' => $request->request_no,
                'type' => $request->document_type->label(),
                'issued_on' => ($request->released_at ?? $request->updated_at)->toDateString(),
                'learner' => $this->mask($request->student->first_name).' '.$this->mask($request->student->last_name),
            ] : null,
            'school' => config('eskwelahub.school_name'),
        ]);
    }

    /** "Maria" becomes "M****". */
    private function mask(string $name): string
    {
        return Str::substr($name, 0, 1).str_repeat('*', max(2, Str::length($name) - 1));
    }
}
