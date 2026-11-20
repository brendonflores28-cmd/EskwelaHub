<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Text and guessed fields from a scan. Nothing becomes a student record until
 * staff check the draft and verify it.
 *
 * @property int $id
 * @property int $scan_upload_id
 * @property string|null $raw_text
 * @property array<string, mixed>|null $parsed
 * @property string $status pending, verified or rejected
 * @property int|null $student_id
 */
#[Fillable(['scan_upload_id', 'raw_text', 'parsed', 'status', 'student_id', 'verified_by', 'verified_at'])]
class OcrDraft extends Model
{
    protected function casts(): array
    {
        return ['parsed' => 'array', 'verified_at' => 'datetime'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ScanUpload::class, 'scan_upload_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
