<?php

namespace App\Http\Requests\Staff;

use App\Enums\Curriculum;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adds or updates a student's enrollment for one school year. */
class EnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'school_year_id' => ['required', 'exists:school_years,id'],
            'grade_level' => ['required', 'integer', 'between:7,12'],
            'section' => ['nullable', 'string', 'max:60'],
            // Optional: when empty it is worked out from the school year and grade (K to 12 for every current school year).
            'curriculum' => ['nullable', Rule::enum(Curriculum::class)],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'general_average' => ['nullable', 'numeric', 'between:60,100'],
        ];
    }
}
