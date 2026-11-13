<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            self::logAuditEvent($model, 'created', null, $model->getAttributes());
        });

        static::updating(function ($model) {
            $dirty = $model->getDirty();
            unset($dirty['updated_at'], $dirty['created_at']);

            if (! empty($dirty)) {
                $oldValues = [];
                $newValues = [];

                foreach ($dirty as $attribute => $newValue) {
                    $oldValues[$attribute] = $model->getOriginal($attribute);
                    $newValues[$attribute] = $newValue;
                }

                self::logAuditEvent($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            self::logAuditEvent($model, 'deleted', $model->getAttributes(), null);
        });
    }

    protected static function logAuditEvent($model, string $action, ?array $oldValues, ?array $newValues, ?string $customDescription = null): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'مستخدم غير مسجل / النظام',
                'action' => $action,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'description' => $customDescription,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Silently continue so auditing never breaks business transactions
            report($e);
        }
    }
}
