<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentType;
use App\Enums\Strand;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An online enrollment form (new Grade 7 or transferee). Staff approve or
 * reject it; approval creates or links the student record.
 *
 * @property int $id
 * @property string|null $reference_no
 * @property int $user_id
 * @property int $school_year_id
 * @property EnrollmentType $type
 * @property int $grade_level
 * @property string $relationship
 * @property string|null $lrn
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $suffix
 * @property string $sex
 * @property Carbon $birthdate
 * @property EnrollmentStatus $status
 * @property string|null $decision_note
 * @property int|null $student_id
 */
#[Fillable([
    'user_id', 'school_year_id', 'type', 'grade_level', 'strand', 'relationship', 'lrn', 'last_name', 'first_name',
    'middle_name', 'suffix', 'sex', 'birthdate', 'birthplace', 'address', 'guardian_name', 'guardian_contact',
    'previous_school',
])]
class EnrollmentApplication extends Model
{
    protected function casts(): array
    {
        return [
            'type' => EnrollmentType::class,
            'strand' => Strand::class,
            'status' => EnrollmentStatus::class,
            'birthdate' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (EnrollmentApplication $a) => $a->status ??= EnrollmentStatus::Submitted);

        // The reference number needs the id, so it is written right after the first insert.
        static::created(function (EnrollmentApplication $a): void {
            $a->reference_no = sprintf('ENR-%d-%06d', now()->year, $a->id);
            $a->saveQuietly();
        });
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' '.mb_substr($this->middle_name, 0, 1).'.' : '';

        return mb_strtoupper($this->last_name).', '.$this->first_name.$middle.($this->suffix ? ' '.$this->suffix : '');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EnrollmentDocument::class);
    }
}
