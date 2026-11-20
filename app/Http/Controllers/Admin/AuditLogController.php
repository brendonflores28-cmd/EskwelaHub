<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/** Read-only audit trail: who viewed or changed which record, and when. */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'log' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'string', 'max:50'],
        ]);

        $entries = Activity::query()
            ->with(['causer:id,name,email', 'subject'])
            ->when($filters['log'] ?? null, fn ($q, $log) => $q->where('log_name', $log))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'when' => $a->created_at->toDateTimeString(),
                'log' => $a->log_name,
                'event' => $a->event,
                'description' => $a->description,
                'who' => $a->causer?->name ?? 'System',
                'subject' => $a->subject_type ? class_basename($a->subject_type).' #'.$a->subject_id : null,
                'ip' => $a->properties['ip'] ?? null,
            ]);

        return Inertia::render('admin/AuditLog', [
            'entries' => $entries,
            'filters' => ['log' => $filters['log'] ?? null, 'event' => $filters['event'] ?? null],
            'logs' => Activity::query()->distinct()->pluck('log_name')->filter()->values(),
            'events' => Activity::query()->distinct()->pluck('event')->filter()->values(),
        ]);
    }
}
