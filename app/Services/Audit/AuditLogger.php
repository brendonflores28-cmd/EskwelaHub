<?php

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Single place to write "who viewed / requested / released what" entries.
 * Field changes on models are logged automatically by the LogsActivity trait;
 * this class covers actions that change nothing, like viewing a record.
 */
class AuditLogger
{
    public const LOG_NAME = 'access';

    public function viewed(Model $subject, string $description = 'Viewed record'): void
    {
        $this->record($subject, 'viewed', $description);
    }

    public function record(Model $subject, string $event, string $description): void
    {
        activity(self::LOG_NAME)
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => str((string) request()->userAgent())->limit(200)->toString(),
            ])
            ->log($description);
    }

    /** Activity model class, for queries in controllers. */
    public static function model(): string
    {
        return Activity::class;
    }
}
