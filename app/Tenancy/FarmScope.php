<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Membatasi semua query ke peternakan yang sedang aktif.
 *
 * Sengaja "fail closed": tanpa peternakan aktif, query tidak mengembalikan data apa pun.
 * Lebih baik layar kosong daripada data peternakan lain bocor.
 */
class FarmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $farmId = app(FarmContext::class)->id();

        if ($farmId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('farm_id'), $farmId);
    }
}
