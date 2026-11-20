<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A scanned old record uploaded by staff for digitization. Private disk only.
 *
 * @property int $id
 * @property int $uploaded_by
 * @property string $original_name
 * @property string $disk_path
 * @property string $mime
 * @property string $engine
 */
#[Fillable(['uploaded_by', 'original_name', 'disk_path', 'mime', 'size', 'engine'])]
class ScanUpload extends Model
{
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function draft(): HasOne
    {
        return $this->hasOne(OcrDraft::class);
    }
}
