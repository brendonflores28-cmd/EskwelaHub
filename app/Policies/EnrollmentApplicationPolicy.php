<?php

namespace App\Policies;

use App\Models\EnrollmentApplication;
use App\Models\User;

/** Staff see all enrollment forms; an applicant sees only their own. */
class EnrollmentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollments.view');
    }

    public function view(User $user, EnrollmentApplication $application): bool
    {
        return $user->can('enrollments.view') || $application->user_id === $user->id;
    }

    /** Review, approve, reject. */
    public function process(User $user, EnrollmentApplication $application): bool
    {
        return $user->can('enrollments.process');
    }
}
