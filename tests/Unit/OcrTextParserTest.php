<?php

use App\Services\Ocr\OcrTextParser;

it('reads a labelled one-line record', function () {
    $text = <<<'TXT'
    LEARNER'S PERMANENT RECORD
    LRN: 1234 5678 9012
    Name: SANTOS, Juan Reyes
    Sex: Male
    Date of Birth: January 5, 2003
    School Year: 2005-2006   Grade 7
    General Average: 87.5
    TXT;

    $parsed = (new OcrTextParser)->parse($text);

    expect($parsed)->toMatchArray([
        'lrn' => '123456789012',
        'last_name' => 'Santos',
        'first_name' => 'Juan',
        'middle_name' => 'Reyes',
        'sex' => 'M',
        'birthdate' => '2003-01-05',
        'school_year' => '2005-2006',
        'grade_level' => 7,
        'general_average' => '87.50',
    ]);
});

it('reads separate name fields and an old "second year" level', function () {
    $text = "Last Name: dela cruz\nFirst Name: maria\nMiddle Name: lopez\nSex: F\nSY 1999-2000\nSecond Year";

    $parsed = (new OcrTextParser)->parse($text);

    expect($parsed['last_name'])->toBe('Dela Cruz')
        ->and($parsed['first_name'])->toBe('Maria')
        ->and($parsed['middle_name'])->toBe('Lopez')
        ->and($parsed['sex'])->toBe('F')
        ->and($parsed['school_year'])->toBe('1999-2000')
        ->and($parsed['grade_level'])->toBe(8);
});

it('leaves unknown fields empty and rejects impossible values', function () {
    $parsed = (new OcrTextParser)->parse("Random smudged text\nSchool Year: 2005-2009\nGrade 3\nGeneral Average: 12");

    expect($parsed['lrn'])->toBeNull()
        ->and($parsed['last_name'])->toBeNull()
        ->and($parsed['birthdate'])->toBeNull()
        ->and($parsed['school_year'])->toBeNull()   // not a one-year span
        ->and($parsed['grade_level'])->toBeNull()   // not a high school grade
        ->and($parsed['general_average'])->toBeNull();
});

it('takes a lone 12-digit number as the LRN', function () {
    expect((new OcrTextParser)->parse('ID 301234567890 end')['lrn'])->toBe('301234567890');
});
