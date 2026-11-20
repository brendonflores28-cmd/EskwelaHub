<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff and admin must confirm two-factor authentication before they can use
 * the records portals. Other users pass through untouched.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            config('eskwelahub.require_staff_2fa')
            && $user?->isStaffMember()
            && ! $user->hasEnabledTwoFactorAuthentication()
        ) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => 'Turn on two-factor authentication to use the staff portal.',
            ]);

            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
