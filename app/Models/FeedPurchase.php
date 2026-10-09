<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FeedPurchase extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'feed_stock_id',
        'purchase_date',
        'quantity_kg',
        'cost_per_kg',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => \App\Casts\DateOnly::class,
    ];

    protected static function booted()
    {
        static::saving(function ($purchase) {
            $purchase->total_cost = round($purchase->quantity_kg * $purchase->cost_per_kg, 2);
        });

        static::created(function ($purchase) {
            FeedStock::receive($purchase->feed_stock_id, (float) $purchase->quantity_kg, (float) $purchase->cost_per_kg);
        });

        static::updated(function ($purchase) {
            if (!$purchase->wasChanged(['feed_stock_id', 'quantity_kg', 'cost_per_kg'])) {
                return;
            }

            FeedStock::unreceive((int) $purchase->getOriginal('feed_stock_id'), (float) $purchase->getOriginal('quantity_kg'), (float) $purchase->getOriginal('cost_per_kg'));
            FeedStock::receive($purchase->feed_stock_id, (float) $purchase->quantity_kg, (float) $purchase->cost_per_kg);
        });

        static::deleted(function ($purchase) {
            if (!$purchase->isForceDeleting()) {
                FeedStock::unreceive($purchase->feed_stock_id, (float) $purchase->quantity_kg, (float) $purchase->cost_per_kg);
            }
        });

        static::restored(function ($purchase) {
            FeedStock::receive($purchase->feed_stock_id, (float) $purchase->quantity_kg, (float) $purchase->cost_per_kg);
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