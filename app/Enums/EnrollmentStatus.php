<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    /** Still waiting for a registrar decision. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
    }
}
