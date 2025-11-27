<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    public static function record(Model $model, ?Authenticatable $user, string $action, array $changes = []): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id'   => $model->getKey(),
            'user_id'        => $user?->getAuthIdentifier(),
            'action'         => $action,
            'changes'        => $changes ?: null,
        ]);
    }
}
