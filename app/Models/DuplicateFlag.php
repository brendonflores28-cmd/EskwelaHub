<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Two student records that look like the same person. Staff decide: dismiss
 * (not the same) or merge.
 *
 * @property int $id
 * @property int $student_a_id the smaller id of the pair
 * @property int $student_b_id
 * @property string $score
 * @property string $reason
 * @property string $status open, dismissed or merged
 */
#[Fillable(['student_a_id', 'student_b_id', 'score', 'reason', 'status', 'resolved_by', 'resolved_at'])]
class DuplicateFlag extends Model
{
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function studentA(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_a_id')->withTrashed();
    }

    public function studentB(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_b_id')->withTrashed();
    }
}
