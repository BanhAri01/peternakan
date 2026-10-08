<?php

namespace App\Audit;

trait RecordsActivity
{
    public static function bootRecordsActivity(): void
    {
        static::created(fn ($model) => ActivityRecorder::model($model, 'created'));
        static::updated(fn ($model) => ActivityRecorder::model($model, 'updated'));
        static::deleted(fn ($model) => ActivityRecorder::model(
            $model,
            method_exists($model, 'isForceDeleting') && $model->isForceDeleting() ? 'force_deleted' : 'deleted'
        ));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => ActivityRecorder::model($model, 'restored'));
        }
    }

    public function auditIgnoredFields(): array
    {
        return property_exists($this, 'auditIgnore') ? $this->auditIgnore : [];
    }

    public function auditMaskedFields(): array
    {
        return property_exists($this, 'auditMask') ? $this->auditMask : [];
    }
}
