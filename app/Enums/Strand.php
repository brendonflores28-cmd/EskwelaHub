<?php

namespace App\Enums;

/**
 * Senior high school (Grades 11 and 12) tracks and strands of the DepEd K to 12
 * curriculum. A learner in Grades 7 to 10 has no strand.
 *
 * Which strands Cawayan National High School really offers is set in
 * config/eskwelahub.php (SCHOOL_STRANDS); only those appear in the forms.
 */
enum Strand: string
{
    case Stem = 'STEM';
    case Abm = 'ABM';
    case Humss = 'HUMSS';
    case Gas = 'GAS';
    case Tvl = 'TVL';
    case Sports = 'SPORTS';
    case ArtsDesign = 'ARTS';

    /** Senior high starts at Grade 11. */
    public const FIRST_SENIOR_HIGH_GRADE = 11;

    public function label(): string
    {
        return match ($this) {
            self::Stem => 'STEM - Science, Technology, Engineering and Mathematics',
            self::Abm => 'ABM - Accountancy, Business and Management',
            self::Humss => 'HUMSS - Humanities and Social Sciences',
            self::Gas => 'GAS - General Academic Strand',
            self::Tvl => 'TVL - Technical-Vocational-Livelihood',
            self::Sports => 'Sports Track',
            self::ArtsDesign => 'Arts and Design Track',
        };
    }

    /** Short text for tables and badges: "STEM", "TVL"... */
    public function short(): string
    {
        return match ($this) {
            self::Sports => 'Sports',
            self::ArtsDesign => 'Arts & Design',
            default => $this->value,
        };
    }

    public function track(): string
    {
        return match ($this) {
            self::Stem, self::Abm, self::Humss, self::Gas => 'Academic Track',
            self::Tvl => 'Technical-Vocational-Livelihood Track',
            self::Sports => 'Sports Track',
            self::ArtsDesign => 'Arts and Design Track',
        };
    }

    /** True for Grades 11 and 12, where a strand is required. */
    public static function appliesTo(int $gradeLevel): bool
    {
        return $gradeLevel >= self::FIRST_SENIOR_HIGH_GRADE;
    }

    /**
     * Strands the school offers (SCHOOL_STRANDS in .env, comma separated codes).
     * Empty or unknown codes mean "all strands".
     *
     * @return list<self>
     */
    public static function offered(): array
    {
        $codes = array_filter(array_map('trim', explode(',', (string) config('eskwelahub.strands'))));
        $offered = array_values(array_filter(array_map(fn (string $c) => self::tryFrom(strtoupper($c)), $codes)));

        return $offered ?: self::cases();
    }

    /** @return list<string> codes for validation rules (Rule::in) */
    public static function offeredValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::offered());
    }

    /** @return list<array{value: string, label: string, short: string}> for the Vue selects */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label(), 'short' => $s->short()], self::offered());
    }
}
