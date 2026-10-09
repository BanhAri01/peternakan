<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedStock extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity;

    protected $fillable = [
        'feed_name',
        'stock_kg',
        'cost_per_kg',
    ];

    protected $casts = [
        'stock_kg'    => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
    ];

    public function purchases()
    {
        return $this->hasMany(FeedPurchase::class);
    }

    public static function receive(int $feedId, float $kg, float $price): void
    {
        $feed = static::lockForUpdate()->find($feedId);
        if (!$feed) {
            return;
        }

        $stock = (float) $feed->stock_kg;
        $total = $stock + $kg;
        if ($total > 0) {
            $feed->cost_per_kg = round((($stock * (float) $feed->cost_per_kg) + ($kg * $price)) / $total, 2);
        }
        $feed->stock_kg = $total;
        $feed->save();
    }

    public static function unreceive(int $feedId, float $kg, float $price): void
    {
        $feed = static::lockForUpdate()->find($feedId);
        if (!$feed) {
            return;
        }

        $stock     = (float) $feed->stock_kg;
        $remaining = $stock - $kg;
        if ($remaining > 0) {
            $avg = (($stock * (float) $feed->cost_per_kg) - ($kg * $price)) / $remaining;
            if ($avg > 0) {
                $feed->cost_per_kg = round($avg, 2);
            }
        }
        $feed->stock_kg = $remaining;
        $feed->save();
    }

    public function usages()
    {
        return $this->hasMany(DailyLogFeed::class);
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyLog::class);
    }

    public function getStockValueAttribute(): float
    {
        return (float) $this->stock_kg * (float) $this->cost_per_kg;
    }
}