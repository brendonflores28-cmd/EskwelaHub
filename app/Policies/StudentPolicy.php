<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Staff and admin work with all records. A student or parent sees only the
 * records linked to their account (and approved by staff).
 */
class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('students.view') || $user->canAccessStudent($student);
    }

    public function create(User $user): bool
    {
        return $user->can('students.manage');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('students.manage');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('students.delete');
    }
}
