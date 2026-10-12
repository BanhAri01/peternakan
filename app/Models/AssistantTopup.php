<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class AssistantTopup extends Model
{
    use BelongsToFarm;

    protected $fillable = ['period', 'amount', 'note', 'created_by'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
