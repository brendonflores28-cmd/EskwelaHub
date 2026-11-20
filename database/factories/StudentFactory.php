<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake learners only. Names are common Filipino names picked at random; none of
 * this data belongs to a real student.
 *
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    private const FIRST_NAMES_F = ['Maria', 'Angelica', 'Jasmine', 'Kristine', 'Mary Grace', 'Princess', 'Hazel', 'Jocelyn', 'Aileen', 'Rhea', 'Cherry', 'Liza', 'Joanna', 'Nicole', 'Shiela', 'Bea', 'Camille', 'Dianne'];

    private const FIRST_NAMES_M = ['Juan', 'Jose', 'Mark', 'John Paul', 'Christian', 'Kevin', 'Jerome', 'Mico', 'Ramil', 'Carlo', 'Joshua', 'Rey', 'Arnel', 'Dennis', 'Paolo', 'Jay', 'Ariel', 'Noel'];

    private const LAST_NAMES = ['Santos', 'Reyes', 'Cruz', 'Dela Cruz', 'Garcia', 'Mendoza', 'Bautista', 'Villanueva', 'Ramos', 'Aquino', 'Castillo', 'Flores', 'Navarro', 'Pascual', 'Salazar', 'Marasigan', 'Magsino', 'Atienza', 'Dimaano', 'Macalintal', 'Comia', 'Panganiban', 'Manalo', 'Lumanglas'];

    private const MIDDLE_NAMES = ['Reyes', 'Santos', 'Cruz', 'Lopez', 'Torres', 'Rivera', 'Gonzales', 'Perez', 'Soriano', 'Mercado'];

    private const PLACES = ['Bongabong, Oriental Mindoro', 'Roxas, Oriental Mindoro', 'Bansud, Oriental Mindoro', 'Calapan City', 'Gloria, Oriental Mindoro'];

    public function definition(): array
    {
        $sex = fake()->randomElement(['M', 'F']);
        $first = fake()->randomElement($sex === 'M' ? self::FIRST_NAMES_M : self::FIRST_NAMES_F);

        return [
            'lrn' => null,
            'last_name' => fake()->randomElement(self::LAST_NAMES),
            'first_name' => $first,
            'middle_name' => fake()->randomElement(self::MIDDLE_NAMES),
            'suffix' => fake()->boolean(3) ? fake()->randomElement(['Jr.', 'III']) : null,
            'sex' => $sex,
            'birthdate' => fake()->dateTimeBetween('-40 years', '-11 years')->format('Y-m-d'),
            'birthplace' => fake()->randomElement(self::PLACES),
            'status' => StudentStatus::Enrolled,
            'is_draft' => false,
        ];
    }

    /** Fake 12-digit LRN. Starts with 9 so it is obvious this is not a real learner number. */
    public function withLrn(): static
    {
        return $this->state(fn () => [
            'lrn' => '9'.str_pad((string) fake()->unique()->numberBetween(0, 99999999999), 11, '0', STR_PAD_LEFT),
        ]);
    }
}
