<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'initial_population',
        'current_population',
        'strain',
        'chick_in_date',
        'initial_age_weeks',
        'status',
    ];

    public function dailyLogs()
    {
        return $this->hasMany(DailyLog::class);
    }
}