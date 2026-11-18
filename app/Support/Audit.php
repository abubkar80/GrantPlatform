<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Append-only audit trail (SRS: every key action is recorded).
 * Never put passwords, bank details or raw evidence in $changes.
 */
final class Audit
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(
        string $action,
        string $subjectType,
        ?int $subjectId,
        ?int $actorId = null,
        ?string $reason = null,
        array $changes = [],
        string $outcome = 'success',
    ): void {
        $now = now();

        DB::table('audit_events')->insert([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'outcome' => $outcome,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'request_id' => request()->headers->get('X-Request-Id') ?? (string) Str::uuid(),
            'reason' => $reason,
            'changes' => json_encode($changes, JSON_THROW_ON_ERROR),
            'occurred_at' => $now,
            'created_at' => $now,
        ]);
    }
}
