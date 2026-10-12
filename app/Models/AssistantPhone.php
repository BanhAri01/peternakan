<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class AssistantPhone extends Model
{
    use BelongsToFarm;

    protected $fillable = ['user_id', 'phone', 'label'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
