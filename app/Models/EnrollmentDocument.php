<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file uploaded with an enrollment form. Stored on the private disk and
 * opened only through a signed, logged route.
 *
 * @property int $id
 * @property int $enrollment_application_id
 * @property string $kind
 * @property string $original_name
 * @property string $disk_path
 * @property string $mime
 * @property int $size
 */
#[Fillable(['enrollment_application_id', 'kind', 'original_name', 'disk_path', 'mime', 'size', 'sha256'])]
class EnrollmentDocument extends Model
{
    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }
}
