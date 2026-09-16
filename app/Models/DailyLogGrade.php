<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLogGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_log_id',
        'egg_grade_id',
        'trays_count',
        'extra_eggs',
        'total_eggs',
        'weight_kg',
    ];

    public function dailyLog()
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function grade()
    {
        return $this->belongsTo(EggGrade::class, 'egg_grade_id');
    }
}