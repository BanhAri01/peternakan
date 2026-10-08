<?php

namespace App\Tenancy;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Pasang di setiap model data peternakan:
 * - query otomatis dibatasi ke peternakan aktif
 * - farm_id otomatis diisi saat membuat data baru (gagal jika tidak ada peternakan aktif)
 */
trait BelongsToFarm
{
    public static function bootBelongsToFarm(): void
    {
        static::addGlobalScope(new FarmScope);

        static::creating(function ($model) {
            if ($model->farm_id) {
                return;
            }

            $farmId = app(FarmContext::class)->id();

            if ($farmId === null) {
                throw new RuntimeException('Tidak bisa menyimpan ' . class_basename($model) . ' tanpa peternakan aktif.');
            }

            $model->farm_id = $farmId;
        });
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    // Query lintas peternakan (khusus admin / command)
    public static function allFarms(): Builder
    {
        return static::withoutGlobalScope(FarmScope::class);
    }
}
