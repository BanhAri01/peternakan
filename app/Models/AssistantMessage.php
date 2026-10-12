<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    use BelongsToFarm;

    protected $fillable = [
        'user_id', 'channel', 'phone', 'role', 'mode', 'status', 'content', 'model',
        'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens', 'cost', 'external_id',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
