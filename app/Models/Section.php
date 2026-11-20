<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_year_id
 * @property int $grade_level
 * @property string $name
 * @property string|null $adviser_name
 */
#[Fillable(['school_year_id', 'grade_level', 'name', 'adviser_name'])]
class Section extends Model
{
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
