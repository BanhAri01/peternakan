<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_name',
        'stock_kg',
        'cost_per_kg',
    ];

    protected $casts = [
        'stock_kg'    => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
    ];
}