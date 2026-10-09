<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Models\Setting;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class FeedCount extends Model
{
    use BelongsToFarm, RecordsActivity;

    protected $fillable = ['count_date', 'feed_stock_id', 'expected_kg', 'counted_kg', 'difference_kg', 'recorded_by'];

    protected $casts = [
        'count_date'    => \App\Casts\DateOnly::class,
        'expected_kg'   => 'decimal:2',
        'counted_kg'    => 'decimal:2',
        'difference_kg' => 'decimal:2',
    ];

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function tolerance(): float
    {
        return (float) (Setting::num('feed_count_tolerance_kg') ?: 0);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->difference_kg) <= self::tolerance();
    }
}
