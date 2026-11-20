<?php

namespace App\Models;

use App\Enums\Curriculum;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per student per school year.
 *
 * @property int $id
 * @property int $student_id
 * @property int $school_year_id
 * @property int|null $section_id
 * @property int $grade_level 7-12
 * @property Curriculum $curriculum
 * @property StudentStatus $status
 * @property string|null $general_average
 */
#[Fillable(['student_id', 'school_year_id', 'section_id', 'grade_level', 'curriculum', 'status', 'general_average'])]
class StudentEnrollment extends Model
{
    protected function casts(): array
    {
        return [
            'curriculum' => Curriculum::class,
            'status' => StudentStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
