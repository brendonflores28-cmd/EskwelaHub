<?php

namespace App\Models;

use App\Enums\LinkStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A user's request to see a student record (self, parent or guardian).
 * Only approved links give access.
 *
 * @property int $id
 * @property int $user_id
 * @property int $student_id
 * @property string $relationship
 * @property LinkStatus $status
 */
#[Fillable(['user_id', 'student_id', 'relationship', 'status', 'reviewed_by', 'reviewed_at'])]
class StudentLink extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => LinkStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('links')
            ->logOnly(['user_id', 'student_id', 'relationship', 'status'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
