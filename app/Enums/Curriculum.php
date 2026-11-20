<?php

namespace App\Enums;

/**
 * Records back to 1999 span two curricula. Both store grade_level as 7-12,
 * where the old 1st-4th year high school levels map to 7-10.
 */
enum Curriculum: string
{
    case Bec = 'bec'; // Basic Education Curriculum (until 2015-2016)
    case K12 = 'k12'; // K to 12 (from 2012 onward)

    public function label(): string
    {
        return $this === self::Bec ? 'BEC (pre K-12)' : 'K to 12';
    }
}
