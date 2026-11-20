<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Sentiment;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Principal: read the satisfaction surveys and their sentiment labels. */
class SurveyController extends Controller
{
    public function index(Request $request): Response
    {
        $sentiment = $request->validate(['sentiment' => ['nullable', 'in:positive,neutral,negative']])['sentiment'] ?? null;

        $surveys = Survey::query()
            ->with(['documentRequest:id,request_no,document_type', 'enrollmentApplication:id,reference_no'])
            ->when($sentiment, fn ($q, $s) => $q->where('sentiment', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Survey $s) => [
                'id' => $s->id,
                'when' => $s->created_at->toDateTimeString(),
                'about' => $s->documentRequest?->request_no ?? $s->enrollmentApplication?->reference_no,
                'overall' => $s->rating_overall,
                'speed' => $s->rating_speed,
                'staff' => $s->rating_staff,
                'comment' => $s->comment,
                'sentiment' => $s->sentiment?->value,
            ]);

        return Inertia::render('admin/Surveys', [
            'surveys' => $surveys,
            'filters' => ['sentiment' => $sentiment],
            'summary' => [
                'total' => Survey::count(),
                'average' => round((float) Survey::avg('rating_overall'), 2),
                'positive' => Survey::where('sentiment', Sentiment::Positive)->count(),
                'neutral' => Survey::where('sentiment', Sentiment::Neutral)->count(),
                'negative' => Survey::where('sentiment', Sentiment::Negative)->count(),
            ],
        ]);
    }
}
