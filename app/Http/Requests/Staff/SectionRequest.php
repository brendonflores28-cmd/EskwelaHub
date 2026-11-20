<?php

namespace App\Http\Requests\Staff;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create or edit a class section. */
class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sections.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Section|null $section */
        $section = $this->route('section');

        return [
            // The year and grade of an existing section cannot change: its learners belong to them.
            'school_year_id' => [$section ? 'prohibited' : 'required', 'exists:school_years,id'],
            'grade_level' => [$section ? 'prohibited' : 'required', 'integer', 'between:7,12'],
            'name' => [
                'required', 'string', 'max:60',
                // One name per grade and school year ("Rizal" twice in Grade 7 would be confusing).
                Rule::unique('sections', 'name')
                    ->where(fn ($q) => $q
                        ->where('school_year_id', $section?->school_year_id ?? $this->input('school_year_id'))
                        ->where('grade_level', $section?->grade_level ?? $this->input('grade_level')))
                    ->ignore($section?->id),
            ],
            'adviser_name' => ['nullable', 'string', 'max:150'],
            'capacity' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'adviser_name' => $this->input('adviser_name') ?: null,
            'capacity' => $this->input('capacity') ?: null,
        ]);
    }
}
