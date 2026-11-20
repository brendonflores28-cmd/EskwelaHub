<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Enrolled = 'enrolled';
    case Graduated = 'graduated';
    case Transferred = 'transferred';
    case Dropped = 'dropped';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
