<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FeedingItem extends Model
{
    use BelongsToFarm;

    protected $fillable = ['feeding_id', 'feed_stock_id', 'feed_kg', 'feed_cost'];

    protected $casts = [
        'feed_kg'   => 'decimal:2',
        'feed_cost' => 'decimal:2',
    ];

    public function feeding()
    {
        return $this->belongsTo(Feeding::class);
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }

    public static function active()
    {
        return static::query()
            ->join('feedings', 'feedings.id', '=', 'feeding_items.feeding_id')
            ->whereNull('feedings.deleted_at');
    }

    public static function dailyUsageByFeed(string $from, string $to, int $days = 7): Collection
    {
        return static::active()
            ->whereBetween('feedings.feed_date', [$from, $to])
            ->selectRaw('feeding_items.feed_stock_id, SUM(feeding_items.feed_kg) * 1.0 / ? as per_day', [$days])
            ->groupBy('feeding_items.feed_stock_id')
            ->pluck('per_day', 'feed_stock_id');
    }
}
