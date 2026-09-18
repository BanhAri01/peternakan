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

    protected $casts = [
        'chick_in_date' => 'date',
    ];
}