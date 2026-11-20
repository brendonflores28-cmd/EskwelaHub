<?php

namespace App\Http\Resources;

use App\Models\EnrollmentApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnrollmentApplication */
class EnrollmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'grade_level' => $this->grade_level,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'decision_note' => $this->decision_note,
            'full_name' => $this->full_name,
            'lrn' => $this->lrn,
            'sex' => $this->sex,
            'birthdate' => $this->birthdate->toDateString(),
            'birthplace' => $this->birthplace,
            'address' => $this->address,
            'guardian_name' => $this->guardian_name,
            'guardian_contact' => $this->guardian_contact,
            'previous_school' => $this->previous_school,
            'relationship' => $this->relationship,
            'school_year' => $this->whenLoaded('schoolYear', fn () => $this->schoolYear->label),
            'student_id' => $this->student_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'applicant' => $this->whenLoaded('user', fn () => ['name' => $this->user->name, 'email' => $this->user->email]),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'id' => $d->id,
                'kind' => $d->kind,
                'original_name' => $d->original_name,
                'size_kb' => (int) round($d->size / 1024),
            ])->values()),
        ];
    }
}
