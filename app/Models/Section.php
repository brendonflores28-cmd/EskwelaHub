<?php

namespace App\Models;

use App\Enums\Strand;
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
#[Fillable(['school_year_id', 'grade_level', 'strand', 'name', 'adviser_name', 'capacity'])]
class Section extends Model
{
    protected function casts(): array
    {
        return ['strand' => Strand::class];
    }

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

    /** "Grade 7 - Rizal", or "Grade 11 STEM - STEM-A" for senior high. */
    public function getDisplayNameAttribute(): string
    {
        $strand = $this->strand ? ' '.$this->strand->short() : '';

        return "Grade {$this->grade_level}{$strand} - {$this->name}";
    }
}
