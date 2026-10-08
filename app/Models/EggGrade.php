<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggGrade extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity;

    protected $fillable = ['name', 'code', 'is_active', 'is_mixed'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mixed'  => 'boolean',
    ];

    // Jenis "Telur Campur" (hasil panen sebelum disortir); dibuat otomatis bila belum ada
    public static function mixed(): self
    {
        $mixed = static::where('is_mixed', true)->first();
        if ($mixed) {
            return $mixed;
        }

        // Pakai jenis bernama "Telur Campur" yang sudah ada agar tidak dobel
        $existing = static::whereRaw('LOWER(TRIM(name)) = ?', ['telur campur'])->first();
        if ($existing) {
            $existing->update(['is_mixed' => true, 'is_active' => true]);

            return $existing;
        }

        return static::create(['name' => 'Telur Campur', 'code' => 'CMP', 'is_mixed' => true, 'is_active' => true]);
    }

    // Jenis hasil sortir (selain telur campur)
    public function scopeSorted($query)
    {
        return $query->where('is_mixed', false);
    }

    public function logGrades()
    {
        return $this->hasMany(DailyLogGrade::class);
    }

    public function sortingItems()
    {
        return $this->hasMany(EggSortingItem::class);
    }

    public function sales()
    {
        return $this->hasMany(EggSale::class);
    }

    public function purchases()
    {
        return $this->hasMany(EggPurchase::class);
    }

    public function getStockKgAttribute(): float
    {
        return \App\Services\EggStock::kg()[$this->id] ?? 0;
    }
}
