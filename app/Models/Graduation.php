<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property int $school_year_id
 * @property Carbon|null $date_graduated
 * @property string|null $honors
 * @property string|null $diploma_no
 * @property string|null $general_average
 */
#[Fillable(['student_id', 'school_year_id', 'date_graduated', 'honors', 'diploma_no', 'general_average'])]
class Graduation extends Model
{
    protected function casts(): array
    {
        return ['date_graduated' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
