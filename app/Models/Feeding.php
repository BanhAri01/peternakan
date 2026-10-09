<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Services\FeedingService;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feeding extends Model
{
    use BelongsToFarm, SoftDeletes, RecordsActivity;

    protected array $auditIgnore = ['client_uuid'];

    protected $fillable = ['client_uuid', 'coop_id', 'feed_date', 'session', 'recorded_by', 'notes'];

    protected $casts = [
        'feed_date' => \App\Casts\DateOnly::class,
        'session'   => 'integer',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function items()
    {
        return $this->hasMany(FeedingItem::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function sessionName(): string
    {
        return FeedingService::sessionName($this->session);
    }

    public function totalKg(): float
    {
        return (float) $this->items->sum('feed_kg');
    }
}
