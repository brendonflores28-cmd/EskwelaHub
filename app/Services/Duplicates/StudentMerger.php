<?php

namespace App\Services\Duplicates;

use App\Models\DocumentRequest;
use App\Models\DuplicateFlag;
use App\Models\EnrollmentApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Merges two records of the same person into one. Nothing is destroyed: the
 * removed record is soft deleted and everything that pointed to it now points
 * to the kept record. Fields the kept record lacks are filled from the other.
 */
class StudentMerger
{
    public function merge(Student $keep, Student $remove, User $by, ?DuplicateFlag $flag = null): Student
    {
        if ($keep->is($remove)) {
            throw new InvalidArgumentException('Cannot merge a record into itself.');
        }

        return DB::transaction(function () use ($keep, $remove, $by, $flag): Student {
            // Enrollment history: keep the kept record's year when both have the same year.
            $years = $keep->enrollments()->pluck('school_year_id');
            $remove->enrollments()->whereNotIn('school_year_id', $years)->update(['student_id' => $keep->id]);
            $remove->enrollments()->delete();

            if (! $keep->graduation()->exists()) {
                $remove->graduation()->update(['student_id' => $keep->id]);
            } else {
                $remove->graduation()->delete();
            }

            // Account links: one per user and student, so drop the ones the kept record already has.
            $linked = $keep->links()->pluck('user_id');
            $remove->links()->whereNotIn('user_id', $linked)->update(['student_id' => $keep->id]);
            $remove->links()->delete();

            DocumentRequest::where('student_id', $remove->id)->update(['student_id' => $keep->id]);
            EnrollmentApplication::where('student_id', $remove->id)->update(['student_id' => $keep->id]);

            // Fill gaps. The LRN is unique, so release it from the removed record first.
            $lrn = $remove->lrn;
            $remove->forceFill(['lrn' => null])->saveQuietly();
            $keep->fill([
                'lrn' => $keep->lrn ?? $lrn,
                'middle_name' => $keep->middle_name ?? $remove->middle_name,
                'birthplace' => $keep->birthplace ?? $remove->birthplace,
            ])->save();

            $remove->delete(); // soft delete

            // The flag of this pair (if any) is closed as merged.
            DuplicateFlag::query()
                ->where('status', 'open')
                ->whereIn('student_a_id', [$keep->id, $remove->id])
                ->whereIn('student_b_id', [$keep->id, $remove->id])
                ->update(['status' => 'merged', 'resolved_by' => $by->id, 'resolved_at' => now()]);

            activity('students')
                ->performedOn($keep)
                ->causedBy($by)
                ->event('merged')
                ->withProperties(['merged_student_id' => $remove->id, 'flag_id' => $flag?->id, 'ip' => request()->ip()])
                ->log("Merged record #{$remove->id} into #{$keep->id}");

            return $keep->refresh();
        });
    }
}
