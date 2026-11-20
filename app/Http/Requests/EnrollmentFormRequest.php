<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentType;
use App\Enums\Strand;
use App\Models\EnrollmentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** The online enrollment form with its document uploads. */
class EnrollmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isTransferee = $this->input('type') === EnrollmentType::Transferee->value;
        $upload = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']; // 5 MB

        return [
            'type' => ['required', Rule::enum(EnrollmentType::class)],
            'grade_level' => [Rule::requiredIf($isTransferee), 'nullable', 'integer', 'between:7,12'],
            'relationship' => ['required', Rule::in(['self', 'parent', 'guardian'])],
            'lrn' => ['nullable', 'digits:12'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'birthdate' => ['required', 'date', 'after:1990-01-01', 'before:today'],
            'birthplace' => ['nullable', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_contact' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'previous_school' => [Rule::requiredIf($isTransferee), 'nullable', 'string', 'max:200'],
            // A transferee into Grade 11 or 12 must choose a strand. New Grade 7 learners have none.
            'strand' => [
                Rule::requiredIf($isTransferee && Strand::appliesTo((int) $this->input('grade_level'))),
                'nullable', Rule::in(Strand::offeredValues()),
            ],
            'psa_birth_certificate' => ['required', ...$upload],
            'report_card' => ['required', ...$upload],
            'good_moral' => [Rule::requiredIf($isTransferee), 'nullable', ...$upload],
            'other_document' => ['nullable', ...$upload],
        ];
    }

    /** One open form per learner: stops double submissions. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $exists = EnrollmentApplication::query()
                ->whereIn('status', [EnrollmentStatus::Submitted, EnrollmentStatus::UnderReview])
                ->whereDate('birthdate', $this->input('birthdate'))
                ->where('last_name', $this->input('last_name'))
                ->where('first_name', $this->input('first_name'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('last_name', 'An enrollment form for this learner is already waiting for review.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        foreach (['lrn', 'middle_name', 'suffix', 'birthplace', 'previous_school', 'strand'] as $field) {
            $this->merge([$field => $this->input($field) ?: null]);
        }
    }

    /** Uploaded files by document kind, for EnrollmentWorkflow::submit(). */
    public function documents(): array
    {
        return collect(['psa_birth_certificate', 'report_card', 'good_moral', 'other_document'])
            ->filter(fn (string $kind) => $this->hasFile($kind))
            ->mapWithKeys(fn (string $kind) => [$kind => $this->file($kind)])
            ->all();
    }

    /** Form fields without the uploaded files. */
    public function fields(): array
    {
        return collect($this->validated())->except(['psa_birth_certificate', 'report_card', 'good_moral', 'other_document'])->all();
    }
}
