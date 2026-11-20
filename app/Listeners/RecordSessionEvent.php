<?php

namespace App\Listeners;

use App\Models\SessionLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Writes sign-in related events to the session log (login, logout, register,
 * failed login, lockout, password reset, two-step). It never stores a password.
 * Actions inside the system go to the audit trail instead.
 */
class RecordSessionEvent
{
    public function handle(object $event): void
    {
        [$name, $user, $email] = match (true) {
            $event instanceof Login => ['login', $event->user, $event->user->email ?? null],
            $event instanceof Logout => ['logout', $event->user, $event->user?->email],
            // "credentials" holds the typed password too: only the email is read here.
            $event instanceof Failed => ['login_failed', $event->user, $event->credentials['email'] ?? $event->user?->email],
            $event instanceof Lockout => ['locked_out', null, (string) $event->request->input('email')],
            $event instanceof Registered => ['registered', $event->user, $event->user->email ?? null],
            $event instanceof Verified => ['email_verified', $event->user, $event->user->email ?? null],
            $event instanceof PasswordReset => ['password_reset', $event->user, $event->user->email ?? null],
            $event instanceof PasswordUpdatedViaController => ['password_changed', $event->user, $event->user->email ?? null],
            $event instanceof TwoFactorAuthenticationEnabled => ['two_factor_enabled', $event->user, $event->user->email ?? null],
            $event instanceof TwoFactorAuthenticationDisabled => ['two_factor_disabled', $event->user, $event->user->email ?? null],
            $event instanceof TwoFactorAuthenticationChallenged => ['two_factor_challenged', $event->user, $event->user->email ?? null],
            $event instanceof TwoFactorAuthenticationFailed => ['two_factor_failed', $event->user, $event->user->email ?? null],
            default => [null, null, null],
        };

        if ($name === null) {
            return;
        }

        $request = request();

        SessionLog::create([
            'user_id' => $user instanceof User ? $user->id : null,
            'email' => $email ? mb_substr((string) $email, 0, 255) : null,
            'event' => $name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 200) : null,
        ]);
    }
}
