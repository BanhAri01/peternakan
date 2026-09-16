<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'egg_grade_id',
        'purchase_date',
        'unit_type',
        'quantity_unit',
        'price_per_unit',
        'weight_kg',
        'total_cost',
        'notes',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total_cost = round($item->quantity_unit * $item->price_per_unit, 2);

            // Konversi ke KG untuk penambahan stok gudang jika belum diisi manual
            if (!$item->weight_kg || $item->weight_kg <= 0) {
                if ($item->unit_type === 'krat') {
                    $item->weight_kg = round($item->quantity_unit * 1.9, 2);
                } elseif ($item->unit_type === 'butir') {
                    $item->weight_kg = round($item->quantity_unit * 0.06, 2);
                } else {
                    $item->weight_kg = $item->quantity_unit;
                }
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grade()
    {
        return $this->belongsTo(EggGrade::class, 'egg_grade_id');
    }
}