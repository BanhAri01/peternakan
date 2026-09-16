<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggGrade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];

    public function logGrades()
    {
        return $this->hasMany(DailyLogGrade::class);
    }

    public function sales()
    {
        return $this->hasMany(EggSale::class);
    }

    public function purchases()
    {
        return $this->hasMany(EggPurchase::class);
    }

    public function getStockKgAttribute()
    {
        $harvested = $this->logGrades()->sum('weight_kg');
        $bought = $this->purchases()->sum('weight_kg'); // Tambahan kulakan dari luar
        $sold = $this->sales()->sum('weight_kg');

        return max(0, round(($harvested + $bought) - $sold, 2));
    }
}