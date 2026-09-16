<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    use HasFactory;

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
    ];

    public function grades()
    {
        return $this->hasMany(DailyLogGrade::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class);
    }
}