<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Support\NameIndex;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Identity of a learner. Per-year data lives in StudentEnrollment and the
 * Graduation record, so records back to 1999 fit the same structure.
 *
 * @property int $id
 * @property string|null $lrn 12-digit Learner Reference Number
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $suffix
 * @property string $sex M or F
 * @property Carbon $birthdate
 * @property string|null $birthplace
 * @property StudentStatus $status
 * @property bool $is_draft
 * @property string $name_key
 * @property string $name_metaphone
 * @property-read string $full_name
 */
#[Fillable(['lrn', 'last_name', 'first_name', 'middle_name', 'suffix', 'sex', 'birthdate', 'birthplace', 'status', 'is_draft'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'status' => StudentStatus::class,
            'is_draft' => 'boolean',
        ];
    }

    /** Keep the search columns in sync with the name fields on every save. */
    protected static function booted(): void
    {
        static::saving(function (Student $student): void {
            $key = NameIndex::key(
                $student->last_name,
                $student->first_name,
                (string) $student->middle_name,
                (string) $student->suffix,
            );
            $student->name_key = $key;
            $student->name_metaphone = NameIndex::metaphone($key);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('students')
            ->logOnly(['lrn', 'last_name', 'first_name', 'middle_name', 'suffix', 'sex', 'birthdate', 'status'])
            ->logOnlyDirty();
    }

    /** "DELA CRUZ, Juan P." style display name. */
    protected function fullName(): Attribute
    {
        return Attribute::get(function (): string {
            $middle = $this->middle_name ? ' '.mb_substr($this->middle_name, 0, 1).'.' : '';
            $suffix = $this->suffix ? ' '.$this->suffix : '';

            return mb_strtoupper($this->last_name).', '.$this->first_name.$middle.$suffix;
        });
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /** The enrollment of the most recent school year. */
    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->latestOfMany('school_year_id');
    }

    public function graduation(): HasOne
    {
        return $this->hasOne(Graduation::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(StudentLink::class);
    }
}
