<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            AuditLog::log('created', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getDirty());
            $newValues = $model->getDirty();

            // Filter out sensitive fields
            $sensitiveFields = ['password', 'remember_token'];
            $oldValues = array_diff_key($oldValues, array_flip($sensitiveFields));
            $newValues = array_diff_key($newValues, array_flip($sensitiveFields));

            if (!empty($newValues)) {
                AuditLog::log('updated', $model, $oldValues, $newValues);
            }
        });

        static::deleted(function (Model $model) {
            AuditLog::log('deleted', $model, $model->getAttributes(), null);
        });
    }
}
