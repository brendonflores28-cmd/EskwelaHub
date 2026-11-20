<?php

use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Search\StudentSearch;

function searchNames(array $filters): array
{
    return app(StudentSearch::class)->search($filters, 1)->getCollection()->pluck('last_name')->all();
}

beforeEach(function () {
    Student::factory()->create(['last_name' => 'Marasigan', 'first_name' => 'Arnel', 'lrn' => '900000000011']);
    Student::factory()->create(['last_name' => 'Dela Cruz', 'first_name' => 'Maria', 'lrn' => '900000000012']);
    Student::factory()->create(['last_name' => 'Santos', 'first_name' => 'Juan', 'lrn' => null]);
});

it('finds a student by LRN prefix', function () {
    expect(searchNames(['q' => '900000000011']))->toBe(['Marasigan']);
    expect(searchNames(['q' => '90000000001']))->toHaveCount(2);
});

it('finds a name despite typos', function () {
    expect(searchNames(['q' => 'Marasagin']))->toContain('Marasigan');
    expect(searchNames(['q' => 'arnel marasigan']))->toContain('Marasigan');
});

it('matches a surname written without the space', function () {
    expect(searchNames(['q' => 'delacruz']))->toContain('Dela Cruz');
});

it('ranks the closest name first and drops unrelated names', function () {
    $names = searchNames(['q' => 'Santoz']);

    expect($names[0])->toBe('Santos')
        ->and($names)->not->toContain('Marasigan');
});

it('filters by school year', function () {
    $year = SchoolYear::create(['label' => '2001-2002', 'start_year' => 2001, 'is_current' => false]);
    $student = Student::where('last_name', 'Santos')->first();
    $student->enrollments()->create([
        'school_year_id' => $year->id, 'grade_level' => 7, 'curriculum' => 'bec', 'status' => 'enrolled',
    ]);

    expect(searchNames(['school_year_id' => $year->id]))->toBe(['Santos']);
});

it('keeps the search columns in sync when a name is edited', function () {
    $student = Student::where('last_name', 'Santos')->first();
    $student->update(['last_name' => 'Villanueva']);

    expect($student->fresh()->name_key)->toContain('villanueva')
        ->and(searchNames(['q' => 'villanueva']))->toContain('Villanueva');
});

it('shows search results to staff on the web page', function () {
    $this->actingAs(staffUser())
        ->get(route('staff.students.index', ['q' => 'Marasigan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/students/Index')
            ->has('students.data', 1));
});
