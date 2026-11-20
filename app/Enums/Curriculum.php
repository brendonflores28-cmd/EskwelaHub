<?php

namespace App\Enums;

/**
 * The curriculum a learner studied under. DepEd's current curriculum is K to 12,
 * so every new enrollment is K to 12. BEC (Basic Education Curriculum) exists
 * only for old records back to 1999.
 *
 * Both store grade_level as 7-12. Under BEC the school had 1st to 4th year
 * high school, stored as Grades 7 to 10.
 */
enum Curriculum: string
{
    case Bec = 'bec';
    case K12 = 'k12';

    /** The first Grade 7 class that followed K to 12 started in school year 2012-2013. */
    public const K12_FIRST_ENTRY_YEAR = 2012;

    public function label(): string
    {
        return $this === self::K12 ? 'K to 12 (current)' : 'BEC (before K to 12)';
    }

    /**
     * Curriculum of a learner in a given school year and grade. It follows the
     * year the learner entered Grade 7 (K to 12 was phased in one grade at a time).
     * Grades 11 and 12 exist only in K to 12.
     */
    public static function forLevel(int $schoolYearStart, int $gradeLevel): self
    {
        $entryYear = $schoolYearStart - ($gradeLevel - 7);

        return $gradeLevel >= 11 || $entryYear >= self::K12_FIRST_ENTRY_YEAR ? self::K12 : self::Bec;
    }
}
