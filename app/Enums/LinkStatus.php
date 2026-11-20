<?php

namespace App\Enums;

/** State of a request to link a user account to a student record. */
enum LinkStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
