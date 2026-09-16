<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'egg_grade_id',
        'sale_date',
        'unit_type',
        'quantity_unit',
        'price_per_unit',
        'weight_kg',
        'trays_count',
        'price_per_kg',
        'total_amount',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'due_date',
        'notes',
    ];

    protected static function booted()
    {
        static::saving(function ($sale) {
            // Hitung tagihan dan konversi berat kg
            if ($sale->unit_type === 'krat') {
                $sale->total_amount = round($sale->quantity_unit * $sale->price_per_unit, 2);
                $sale->trays_count = (int) $sale->quantity_unit;
                if (!$sale->weight_kg || $sale->weight_kg <= 0) {
                    $sale->weight_kg = round($sale->quantity_unit * 1.9, 2);
                }
                $sale->price_per_kg = $sale->weight_kg > 0 ? round($sale->total_amount / $sale->weight_kg, 2) : 0;
            } elseif ($sale->unit_type === 'butir') {
                $sale->total_amount = round($sale->quantity_unit * $sale->price_per_unit, 2);
                $sale->trays_count = 0;
                if (!$sale->weight_kg || $sale->weight_kg <= 0) {
                    $sale->weight_kg = round($sale->quantity_unit * 0.06, 2);
                }
                $sale->price_per_kg = $sale->weight_kg > 0 ? round($sale->total_amount / $sale->weight_kg, 2) : 0;
            } else {
                $sale->unit_type = 'kg';
                $sale->weight_kg = $sale->quantity_unit;
                $sale->price_per_kg = $sale->price_per_unit;
                $sale->total_amount = round($sale->weight_kg * $sale->price_per_unit, 2);
            }

            // Piutang
            $paid = $sale->paid_amount ?? 0;
            $debt = max(0, $sale->total_amount - $paid);
            $sale->debt_amount = round($debt, 2);

            // Status
            if ($sale->debt_amount <= 0) {
                $sale->payment_status = 'paid';
            } elseif ($paid > 0 && $sale->debt_amount > 0) {
                $sale->payment_status = 'partial';
            } else {
                $sale->payment_status = 'unpaid';
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function grade()
    {
        return $this->belongsTo(EggGrade::class, 'egg_grade_id');
    }
}