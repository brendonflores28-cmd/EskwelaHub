<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Graduation data of a student (one record per student). */
class GraduationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $graduation = $this->route('student')->graduation;

        return [
            'school_year_id' => ['required', 'exists:school_years,id'],
            'date_graduated' => ['nullable', 'date'],
            'honors' => ['nullable', 'string', 'max:100'],
            'diploma_no' => ['nullable', 'string', 'max:50', Rule::unique('graduations', 'diploma_no')->ignore($graduation?->id)],
            'general_average' => ['nullable', 'numeric', 'between:60,100'],
        ];
    }
}
