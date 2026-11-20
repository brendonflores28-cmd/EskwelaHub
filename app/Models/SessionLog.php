<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sign-in related event. Not the audit trail: see App\Services\Audit\AuditLogger
 * for what users do inside the system.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property string $event
 * @property string|null $ip
 * @property string|null $user_agent
 */
#[Fillable(['user_id', 'email', 'event', 'ip', 'user_agent'])]
class SessionLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    /** Events recorded, with the label shown on the page. @var array<string, string> */
    public const EVENTS = [
        'login' => 'Logged in',
        'login_failed' => 'Failed login',
        'locked_out' => 'Locked out (too many tries)',
        'logout' => 'Logged out',
        'registered' => 'Registered',
        'email_verified' => 'Email verified',
        'password_reset' => 'Password reset',
        'password_changed' => 'Password changed',
        'two_factor_enabled' => 'Two-step turned on',
        'two_factor_disabled' => 'Two-step turned off',
        'two_factor_challenged' => 'Two-step code asked',
        'two_factor_failed' => 'Wrong two-step code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Records older than one year are deleted by `php artisan model:prune` (scheduled daily). */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subYear());
    }
}
