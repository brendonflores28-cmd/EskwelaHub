<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A class section of one grade in one school year ("Grade 7 - Rizal").
 * Learners belong to a section through their yearly enrollment.
 *
 * @property int $id
 * @property int $school_year_id
 * @property int $grade_level 7 to 12
 * @property string $name
 * @property string|null $adviser_name
 * @property int|null $capacity maximum learners, null = no limit
 * @property-read int|null $enrollments_count
 */
#[Fillable(['school_year_id', 'grade_level', 'name', 'adviser_name', 'capacity'])]
class Section extends Model
{
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /** Free places left, or null when the section has no limit. */
    public function seatsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->enrollments()->count());
    }

    /** "Grade 7 - Rizal" */
    public function getDisplayNameAttribute(): string
    {
        return "Grade {$this->grade_level} - {$this->name}";
    }
}
