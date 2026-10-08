<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

// HP/tablet kandang yang didaftarkan pemilik. Di perangkat ini pekerja cukup pilih nama + PIN.
class Device extends Model
{
    use BelongsToFarm, RecordsActivity;

    protected array $auditIgnore = ['uuid', 'user_agent', 'last_seen_at'];

    protected $fillable = ['uuid', 'name', 'is_active', 'user_agent', 'last_seen_at', 'created_by'];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
