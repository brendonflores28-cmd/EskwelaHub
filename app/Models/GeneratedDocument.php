<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A PDF made for a request. The file lives on the private disk
 * (storage/app/private), never in public/.
 *
 * @property int $id
 * @property int $document_request_id
 * @property string $disk_path
 * @property string $sha256
 * @property int $size
 */
#[Fillable(['document_request_id', 'disk_path', 'sha256', 'size', 'generated_at'])]
class GeneratedDocument extends Model
{
    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class, 'document_request_id');
    }
}
