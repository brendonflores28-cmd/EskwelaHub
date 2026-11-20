<?php

namespace App\Services\Ocr;

use Carbon\Carbon;
use Throwable;

/**
 * Guesses student fields from OCR text of an old school record.
 *
 * OCR is never exact, so every field is only a suggestion: staff must check
 * and correct the draft before it becomes a record. Anything not recognised
 * stays null.
 */
class OcrTextParser
{
    /**
     * @return array{lrn: ?string, last_name: ?string, first_name: ?string, middle_name: ?string, sex: ?string, birthdate: ?string, school_year: ?string, grade_level: ?int, general_average: ?string}
     */
    public function parse(string $text): array
    {
        // Scans often put O for 0 or l for 1 inside numbers; fix them only where digits are expected.
        $text = str_replace("\r", '', $text);

        return [
            ...$this->parseName($text),
            'lrn' => $this->lrn($text),
            'sex' => $this->sex($text),
            'birthdate' => $this->birthdate($text),
            'school_year' => $this->schoolYear($text),
            'grade_level' => $this->gradeLevel($text),
            'general_average' => $this->average($text),
        ];
    }

    private function lrn(string $text): ?string
    {
        if (preg_match('/LRN\D{0,15}(\d[\d\s-]{10,16}\d)/i', $text, $m)) {
            $digits = preg_replace('/\D/', '', $m[1]);

            return strlen($digits) === 12 ? $digits : null;
        }

        // Any lone 12-digit number is most likely the LRN.
        return preg_match('/(?<!\d)(\d{12})(?!\d)/', $text, $m) ? $m[1] : null;
    }

    /** @return array{last_name: ?string, first_name: ?string, middle_name: ?string} */
    private function parseName(string $text): array
    {
        $empty = ['last_name' => null, 'first_name' => null, 'middle_name' => null];

        // Separate labelled fields: "Last Name: SANTOS", "First Name: Juan", "Middle Name: Reyes"
        $last = $this->labelled($text, 'last\s*name|surname|family\s*name');
        $first = $this->labelled($text, 'first\s*name|given\s*name');
        if ($last && $first) {
            return ['last_name' => $this->title($last), 'first_name' => $this->title($first), 'middle_name' => ($m = $this->labelled($text, 'middle\s*name')) ? $this->title($m) : null];
        }

        // One line: "Name: SANTOS, Juan Reyes"
        $full = $this->labelled($text, 'name(?:\s+of\s+(?:learner|student|pupil))?|learner|student');
        if ($full && str_contains($full, ',')) {
            [$lastPart, $rest] = array_map('trim', explode(',', $full, 2));
            $parts = preg_split('/\s+/', $rest) ?: [];
            $middle = count($parts) > 1 ? array_pop($parts) : null;

            return [
                'last_name' => $this->title($lastPart),
                'first_name' => $this->title(implode(' ', $parts)),
                'middle_name' => $middle ? $this->title(rtrim($middle, '.')) : null,
            ];
        }

        return $empty;
    }

    private function sex(string $text): ?string
    {
        if (preg_match('/\bsex\b\W{0,5}(male|female|m|f)\b/i', $text, $m)) {
            return strtoupper(substr($m[1], 0, 1));
        }

        return null;
    }

    private function birthdate(string $text): ?string
    {
        if (! preg_match('/(?:date\s*of\s*birth|birth\s*date|birthday|DOB)\W{0,5}([^\n]{6,30})/i', $text, $m)) {
            return null;
        }

        try {
            return Carbon::parse(trim($m[1]))->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function schoolYear(string $text): ?string
    {
        // "SY 2005-2006", "School Year: 2005 - 2006"
        if (preg_match('/(?:s\.?y\.?|school\s*year)\W{0,5}(\d{4})\s*[-\/]\s*(\d{4})/i', $text, $m)) {
            return ((int) $m[2] === (int) $m[1] + 1) ? "{$m[1]}-{$m[2]}" : null;
        }

        return null;
    }

    private function gradeLevel(string $text): ?int
    {
        if (preg_match('/grade\s*(?:level)?\W{0,3}(\d{1,2})\b/i', $text, $m)) {
            $grade = (int) $m[1];

            return ($grade >= 7 && $grade <= 12) ? $grade : null;
        }
        if (preg_match('/\b(first|second|third|fourth|1st|2nd|3rd|4th)\s*year\b/i', $text, $m)) {
            return ['first' => 7, '1st' => 7, 'second' => 8, '2nd' => 8, 'third' => 9, '3rd' => 9, 'fourth' => 10, '4th' => 10][strtolower($m[1])];
        }

        return null;
    }

    private function average(string $text): ?string
    {
        if (preg_match('/(?:general\s*average|gen\.?\s*ave\.?|final\s*grade)\W{0,5}(\d{2,3}(?:\.\d{1,2})?)/i', $text, $m)) {
            $value = (float) $m[1];

            return ($value >= 60 && $value <= 100) ? number_format($value, 2, '.', '') : null;
        }

        return null;
    }

    /** Text after "Label:" up to the end of the line. */
    private function labelled(string $text, string $labelPattern): ?string
    {
        if (preg_match('/(?:^|\n)\s*(?:'.$labelPattern.')\s*[:\-]\s*([^\n]+)/i', $text, $m)) {
            $value = trim(preg_replace('/\s{2,}.*/', '', $m[1]));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function title(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim($value)), MB_CASE_TITLE);
    }
}
