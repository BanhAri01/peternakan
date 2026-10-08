<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    protected $fillable = [
        'coop_id',
        'log_date',
        'mortality',
        'cull',
        'feed_stock_id',
        'feed_consumed_kg',
        'feed_cost_total',
        'eggs_total_count',
        'eggs_total_kg',
        'hdp_percentage',
        'fcr',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'log_date' => \App\Casts\DateOnly::class,
        'feed_consumed_kg' => 'decimal:2',
        'feed_cost_total' => 'decimal:2',
        'eggs_total_kg' => 'decimal:2',
        'hdp_percentage' => 'decimal:2',
        'fcr' => 'decimal:2',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }

    public function grades()
    {
        return $this->hasMany(DailyLogGrade::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}