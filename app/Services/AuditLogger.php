<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public static function log(string $action, ?string $entity = null, string|int|null $entityId = null, ?int $userId = null): void
    {
        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'logged_at' => now(),
        ]);
    }
}