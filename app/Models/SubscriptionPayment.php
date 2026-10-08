<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use BelongsToFarm;

    public const STATUSES = [
        'pending' => ['label' => 'Menunggu pembayaran', 'tone' => 'warning'],
        'paid'    => ['label' => 'Lunas', 'tone' => 'success'],
        'failed'  => ['label' => 'Gagal / dibatalkan', 'tone' => 'danger'],
        'expired' => ['label' => 'Kedaluwarsa', 'tone' => 'neutral'],
    ];

    protected $fillable = [
        'user_id', 'reference', 'gateway', 'plan', 'months', 'amount', 'status', 'method',
        'gateway_ref', 'redirect_url', 'paid_at', 'period_from', 'period_until', 'last_payload',
    ];

    protected $casts = [
        'paid_at'      => 'datetime',
        'period_from'  => \App\Casts\DateOnly::class,
        'period_until' => \App\Casts\DateOnly::class,
        'last_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'neutral';
    }
}
