<?php

namespace App\Http\Resources;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        // Only present on list pages, where the controller eager loads it.
        $latest = $this->relationLoaded('latestEnrollment') ? $this->latestEnrollment : null;

        return [
            'id' => $this->id,
            'lrn' => $this->lrn,
            'full_name' => $this->full_name,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'suffix' => $this->suffix,
            'sex' => $this->sex,
            'birthdate' => $this->birthdate?->toDateString(),
            'birthplace' => $this->birthplace,
            'status' => $this->status->value,
            'is_draft' => $this->is_draft,
            'latest_enrollment' => $latest ? $this->enrollmentArray($latest) : null,
            'enrollments' => $this->whenLoaded('enrollments', fn () => $this->enrollments
                ->sortByDesc('school_year_id')
                ->map(fn ($e) => $this->enrollmentArray($e))
                ->values()),
            'graduation' => $this->whenLoaded('graduation', fn () => $this->graduation ? [
                'school_year_id' => $this->graduation->school_year_id,
                'school_year' => $this->graduation->schoolYear?->label,
                'date_graduated' => $this->graduation->date_graduated?->toDateString(),
                'honors' => $this->graduation->honors,
                'diploma_no' => $this->graduation->diploma_no,
                'general_average' => $this->graduation->general_average,
            ] : null),
        ];
    }

    /** @return array<string, mixed>|null */
    private function enrollmentArray(?StudentEnrollment $e): ?array
    {
        if (! $e) {
            return null;
        }

        return [
            'id' => $e->id,
            'school_year_id' => $e->school_year_id,
            'school_year' => $e->schoolYear?->label,
            'grade_level' => $e->grade_level,
            'section' => $e->section?->name,
            'curriculum' => $e->curriculum->value,
            'status' => $e->status->value,
            'general_average' => $e->general_average,
        ];
    }
}
