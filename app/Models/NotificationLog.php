<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use BelongsToFarm;

    protected $fillable = ['farm_id', 'kind', 'sent_on', 'target', 'status', 'message', 'error'];

    protected $casts = [
        'sent_on' => \App\Casts\DateOnly::class,
    ];
}
