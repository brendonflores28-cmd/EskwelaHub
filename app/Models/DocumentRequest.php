<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A request for a school document. Status changes go through
 * App\Services\Requests\RequestWorkflow, never by editing `status` directly.
 *
 * @property int $id
 * @property string|null $request_no
 * @property int $user_id
 * @property int $student_id
 * @property DocumentType $document_type
 * @property string $purpose
 * @property int $copies
 * @property RequestStatus $status
 * @property string|null $remarks
 * @property string $verification_token
 * @property Carbon|null $released_at
 */
#[Fillable(['user_id', 'student_id', 'document_type', 'purpose', 'copies', 'remarks'])]
class DocumentRequest extends Model
{
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => RequestStatus::class,
            'reviewed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentRequest $request): void {
            $request->status ??= RequestStatus::Submitted;
            $request->verification_token = Str::random(40);
        });

        // The number needs the id, so it is written right after the first insert.
        static::created(function (DocumentRequest $request): void {
            $request->request_no = sprintf('REQ-%d-%06d', now()->year, $request->id);
            $request->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class)->orderBy('id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(GeneratedDocument::class)->latestOfMany();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
