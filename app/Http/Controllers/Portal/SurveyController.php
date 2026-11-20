<?php

namespace App\Http\Controllers\Portal;

use App\Contracts\SentimentAnalyzer;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\EnrollmentApplication;
use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Satisfaction survey after a finished transaction. Only the person who made
 * the request answers, once, and only when the transaction is finished.
 */
class SurveyController extends Controller
{
    public function __construct(private readonly SentimentAnalyzer $sentiment) {}

    public function forRequest(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_unless($documentRequest->user_id === $request->user()->id, 403);
        abort_unless($documentRequest->status === RequestStatus::Released, 403);

        return $this->save($request, ['document_request_id' => $documentRequest->id]);
    }

    public function forEnrollment(Request $request, EnrollmentApplication $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        abort_unless(! $application->status->isOpen(), 403); // approved or rejected

        return $this->save($request, ['enrollment_application_id' => $application->id]);
    }

    /** @param  array<string, int>  $subject */
    private function save(Request $request, array $subject): RedirectResponse
    {
        $data = $request->validate([
            'rating_overall' => ['required', 'integer', 'between:1,5'],
            'rating_speed' => ['required', 'integer', 'between:1,5'],
            'rating_staff' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $already = Survey::query()->where('user_id', $request->user()->id)->where($subject)->exists();
        if ($already) {
            throw ValidationException::withMessages(['rating_overall' => 'You already answered this survey.']);
        }

        // The comment (if any) gets a positive / neutral / negative label.
        $comment = trim((string) ($data['comment'] ?? ''));
        $result = $comment !== '' ? $this->sentiment->analyze($comment) : null;

        Survey::create([
            ...$subject,
            ...$data,
            'comment' => $comment !== '' ? $comment : null,
            'user_id' => $request->user()->id,
            'sentiment' => $result?->label,
            'sentiment_score' => $result?->score,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Thank you for your feedback!']);

        return back();
    }
}
