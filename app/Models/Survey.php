<?php

namespace App\Models;

use App\Enums\Sentiment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Client satisfaction survey after a transaction (document request or
 * enrollment). The comment gets a sentiment label when it is saved.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $document_request_id
 * @property int|null $enrollment_application_id
 * @property int $rating_overall 1 to 5
 * @property int $rating_speed 1 to 5
 * @property int $rating_staff 1 to 5
 * @property string|null $comment
 * @property Sentiment|null $sentiment
 * @property string|null $sentiment_score
 */
#[Fillable([
    'user_id', 'document_request_id', 'enrollment_application_id', 'rating_overall', 'rating_speed',
    'rating_staff', 'comment', 'sentiment', 'sentiment_score',
])]
class Survey extends Model
{
    protected function casts(): array
    {
        return ['sentiment' => Sentiment::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function enrollmentApplication(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class);
    }
}
