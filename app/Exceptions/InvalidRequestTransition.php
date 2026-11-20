<?php

namespace App\Exceptions;

use App\Enums\RequestStatus;
use RuntimeException;

/** Thrown when someone tries a status change the workflow does not allow. */
class InvalidRequestTransition extends RuntimeException
{
    public static function between(RequestStatus $from, RequestStatus $to): self
    {
        return new self("A request that is {$from->label()} cannot become {$to->label()}.");
    }
}
