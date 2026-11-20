<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a request's status history. Rows are only added, never edited.
 *
 * @property int $id
 * @property int $document_request_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by null when the system changed it
 * @property string|null $note
 */
#[Fillable(['document_request_id', 'from_status', 'to_status', 'changed_by', 'note'])]
class RequestStatusHistory extends Model
{
    public const UPDATED_AT = null;

    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class, 'document_request_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
