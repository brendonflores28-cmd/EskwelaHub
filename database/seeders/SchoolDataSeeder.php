<?php

namespace Database\Seeders;

use App\Enums\Curriculum;
use App\Enums\StudentStatus;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;

/**
 * FAKE data for local development: school years 1999 to now with learners that
 * move through grades 7-12. Never run on production (DatabaseSeeder checks).
 */
class SchoolDataSeeder extends Seeder
{
    private const SECTIONS = ['Rizal', 'Bonifacio', 'Mabini', 'Luna', 'Aguinaldo'];

    /** Learners per Grade 7 cohort. */
    private const COHORT_SIZE = 14;

    public function run(): void
    {
        $firstYear = (int) config('eskwelahub.first_school_year');
        $currentStart = $this->currentSchoolYearStart();

        // 1. School years. The newest one is "current".
        $years = [];
        for ($y = $firstYear; $y <= $currentStart; $y++) {
            $years[$y] = SchoolYear::updateOrCreate(
                ['start_year' => $y],
                ['label' => SchoolYear::labelFor($y), 'is_current' => $y === $currentStart],
            );
        }

        // 2. One cohort of learners per year, from Grade 7 until they graduate.
        foreach ($years as $entryYear => $_) {
            $this->seedCohort($entryYear, $years, $currentStart);
        }

        // 3. A demo pair of look-alike records so the "Record alerts" page has something to show.
        $original = Student::whereNotNull('lrn')->orderBy('id')->skip(20)->firstOrFail();
        Student::factory()->create([
            'last_name' => $original->last_name,
            'first_name' => $original->first_name.'e',   // typo: "Mark" becomes "Marke"
            'middle_name' => null,
            'birthdate' => $original->birthdate->toDateString(),
        ]);
    }

    /** School year starts in June: before June the current school year began last year. */
    private function currentSchoolYearStart(): int
    {
        $now = now();

        return $now->month >= 6 ? $now->year : $now->year - 1;
    }

    /** @param  array<int, SchoolYear>  $years */
    private function seedCohort(int $entryYear, array $years, int $currentStart): void
    {
        // Before the K to 12 program the school ran Grades 7-10 (1st to 4th year).
        $isK12 = $entryYear >= 2016;
        $lastGrade = $isK12 ? 12 : 10;
        $hasLrn = $entryYear >= 2012;

        for ($i = 0; $i < self::COHORT_SIZE; $i++) {
            $factory = Student::factory();
            $student = ($hasLrn ? $factory->withLrn() : $factory)->create([
                // Born about 12 years before entering Grade 7.
                'birthdate' => fake()->dateTimeBetween(($entryYear - 13).'-01-01', ($entryYear - 12).'-12-31')->format('Y-m-d'),
            ]);

            $dropAfter = fake()->boolean(6) ? fake()->numberBetween(7, $lastGrade - 1) : null;
            $finishedAll = true;

            for ($grade = 7; $grade <= $lastGrade; $grade++) {
                $schoolYearStart = $entryYear + ($grade - 7);
                if ($schoolYearStart > $currentStart) {
                    $finishedAll = false;
                    break;
                }

                $section = Section::firstOrCreate([
                    'school_year_id' => $years[$schoolYearStart]->id,
                    'grade_level' => $grade,
                    'name' => self::SECTIONS[$i % count(self::SECTIONS)],
                ]);

                $student->enrollments()->create([
                    'school_year_id' => $years[$schoolYearStart]->id,
                    'section_id' => $section->id,
                    'grade_level' => $grade,
                    'curriculum' => $isK12 ? Curriculum::K12 : Curriculum::Bec,
                    'status' => StudentStatus::Enrolled,
                    'general_average' => fake()->randomFloat(2, 75, 98),
                ]);

                if ($dropAfter === $grade) {
                    $student->update(['status' => fake()->randomElement([StudentStatus::Dropped, StudentStatus::Transferred])]);
                    $finishedAll = false;
                    break;
                }
            }

            if ($finishedAll && $entryYear + ($lastGrade - 7) < $currentStart) {
                $gradYear = $entryYear + ($lastGrade - 7);
                $student->update(['status' => StudentStatus::Graduated]);
                $student->graduation()->create([
                    'school_year_id' => $years[$gradYear]->id,
                    'date_graduated' => ($gradYear + 1).'-03-'.fake()->numberBetween(20, 31),
                    'honors' => fake()->boolean(12) ? fake()->randomElement(['With Honors', 'With High Honors']) : null,
                    'diploma_no' => 'FAKE-'.$gradYear.'-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                    'general_average' => fake()->randomFloat(2, 78, 97),
                ]);
            }
        }
    }
}
