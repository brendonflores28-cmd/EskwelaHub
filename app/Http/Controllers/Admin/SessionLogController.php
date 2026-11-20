<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SessionLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Session logs: who signed in, signed out, registered or failed to sign in.
 * What people do inside the system is the audit trail (AuditLogController).
 */
class SessionLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'], // part of an email address or IP
        ]);

        $logs = SessionLog::query()
            ->with('user:id,name')
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(
                fn ($w) => $w->where('email', 'like', '%'.$term.'%')->orWhere('ip', 'like', '%'.$term.'%')
            ))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (SessionLog $l) => [
                'id' => $l->id,
                'when' => $l->created_at->toDateTimeString(),
                'event' => $l->event,
                'label' => SessionLog::EVENTS[$l->event] ?? $l->event,
                'who' => $l->user?->name,
                'email' => $l->email,
                'ip' => $l->ip,
                'browser' => $l->user_agent,
            ]);

        return Inertia::render('admin/SessionLogs', [
            'logs' => $logs,
            'filters' => ['event' => $filters['event'] ?? null, 'q' => $filters['q'] ?? ''],
            'events' => collect(SessionLog::EVENTS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            // Quick numbers for the last 24 hours.
            'recent' => [
                'logins' => SessionLog::where('event', 'login')->where('created_at', '>=', now()->subDay())->count(),
                'failed' => SessionLog::whereIn('event', ['login_failed', 'locked_out'])->where('created_at', '>=', now()->subDay())->count(),
                'registered' => SessionLog::where('event', 'registered')->where('created_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }
}
