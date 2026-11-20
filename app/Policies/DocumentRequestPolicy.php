<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use App\Models\User;

/**
 * Staff work with all requests. A requester sees only their own, and can
 * download the PDF only after the registrar released it.
 */
class DocumentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('requests.view');
    }

    public function view(User $user, DocumentRequest $request): bool
    {
        return $user->can('requests.view') || $request->user_id === $user->id;
    }

    /** Review, approve, reject, release, generate again. */
    public function process(User $user, DocumentRequest $request): bool
    {
        return $user->can('requests.process');
    }

    public function download(User $user, DocumentRequest $request): bool
    {
        if ($user->can('requests.process')) {
            return in_array($request->status, [RequestStatus::Ready, RequestStatus::Released], true);
        }

        return $request->user_id === $user->id && $request->status === RequestStatus::Released;
    }
}
