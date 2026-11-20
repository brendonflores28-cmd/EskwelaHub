<?php

namespace App\Enums;

/**
 * Life of a document request.
 *
 *   submitted -> under_review -> approved -> processing -> ready -> released
 *        \             \-> rejected
 *         \-> approved / rejected (staff may skip the review step)
 *
 * "processing" and "ready" are set by the PDF job, not by staff.
 */
enum RequestStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processing = 'processing';
    case Ready = 'ready';
    case Released = 'released';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Approved, self::Rejected],
            self::UnderReview => [self::Approved, self::Rejected],
            self::Approved => [self::Processing],
            self::Processing => [self::Ready],
            self::Ready => [self::Released],
            self::Rejected, self::Released => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    /** Requests the registrar still has to act on. */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Rejected, self::Released], true);
    }
}
