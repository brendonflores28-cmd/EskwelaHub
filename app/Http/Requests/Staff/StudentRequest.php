<?php

namespace App\Http\Requests\Staff;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates creating and editing a student's identity data. */
class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            ? $this->user()->can('update', $student)
            : $this->user()->can('create', Student::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            // LRN is optional: records from before about 2012 have none.
            'lrn' => ['nullable', 'digits:12', Rule::unique('students', 'lrn')->ignore($student?->id)],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'birthdate' => ['required', 'date', 'after:1940-01-01', 'before:today'],
            'birthplace' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Empty strings from the form become null so the unique rule and nullable columns behave.
        $this->merge([
            'lrn' => $this->input('lrn') ?: null,
            'middle_name' => $this->input('middle_name') ?: null,
            'suffix' => $this->input('suffix') ?: null,
            'birthplace' => $this->input('birthplace') ?: null,
        ]);
    }
}
