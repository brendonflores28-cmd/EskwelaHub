<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $label e.g. "1999-2000"
 * @property int $start_year
 * @property bool $is_current
 */
#[Fillable(['label', 'start_year', 'is_current'])]
class SchoolYear extends Model
{
    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function graduations(): HasMany
    {
        return $this->hasMany(Graduation::class);
    }

    /** Build the label ("2026-2027") for the school year that starts in $startYear. */
    public static function labelFor(int $startYear): string
    {
        return $startYear.'-'.($startYear + 1);
    }
}
