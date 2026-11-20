<?php

namespace App\Policies;

use App\Models\StudentLink;
use App\Models\User;

class StudentLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('links.review');
    }

    public function review(User $user, StudentLink $link): bool
    {
        return $user->can('links.review');
    }
}
