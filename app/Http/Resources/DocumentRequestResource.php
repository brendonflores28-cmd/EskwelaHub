<?php

namespace App\Http\Resources;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentRequest */
class DocumentRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_no' => $this->request_no,
            'document_type' => $this->document_type->value,
            'document_label' => $this->document_type->label(),
            'purpose' => $this->purpose,
            'copies' => $this->copies,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toDateTimeString(),
            'released_at' => $this->released_at?->toDateTimeString(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'full_name' => $this->student->full_name,
                'lrn' => $this->student->lrn,
            ]),
            'requester' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'has_document' => $this->whenLoaded('document', fn () => $this->document !== null),
            'history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($h) => [
                'to_status' => $h->to_status,
                'by' => $h->changer?->name ?? 'System',
                'note' => $h->note,
                'at' => $h->created_at?->toDateTimeString(),
            ])->values()),
        ];
    }
}
