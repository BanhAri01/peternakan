<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DailyLogFeed extends Model
{
    use BelongsToFarm;

    protected $fillable = ['daily_log_id', 'feed_stock_id', 'feed_kg', 'feed_cost'];

    protected $casts = [
        'feed_kg'   => 'decimal:2',
        'feed_cost' => 'decimal:2',
    ];

    public function dailyLog()
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }

    public static function dailyUsageByFeed(string $from, string $to, int $days = 7): Collection
    {
        return static::query()
            ->join('daily_logs', 'daily_logs.id', '=', 'daily_log_feeds.daily_log_id')
            ->whereNull('daily_logs.deleted_at')
            ->whereBetween('daily_logs.log_date', [$from, $to])
            ->selectRaw('daily_log_feeds.feed_stock_id, SUM(daily_log_feeds.feed_kg) * 1.0 / ? as per_day', [$days])
            ->groupBy('daily_log_feeds.feed_stock_id')
            ->pluck('per_day', 'feed_stock_id');
    }
}
