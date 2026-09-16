<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeedPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'feed_stock_id',
        'purchase_date',
        'quantity_kg',
        'cost_per_kg',
        'total_cost',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($purchase) {
            $purchase->total_cost = round($purchase->quantity_kg * $purchase->cost_per_kg, 2);
        });

        static::created(function ($purchase) {
            $feed = FeedStock::find($purchase->feed_stock_id);
            if ($feed) {
                // Perbarui modal per kg rata-rata tertimbang
                $currentStock = $feed->stock_kg;
                $currentPrice = $feed->cost_per_kg;
                $newStock = $purchase->quantity_kg;
                $newPrice = $purchase->cost_per_kg;

                $totalQty = $currentStock + $newStock;
                if ($totalQty > 0) {
                    $avgCost = (($currentStock * $currentPrice) + ($newStock * $newPrice)) / $totalQty;
                    $feed->cost_per_kg = round($avgCost, 2);
                }
                
                $feed->stock_kg += $newStock;
                $feed->save();
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }
}