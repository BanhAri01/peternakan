<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

// Satu peternakan (pelanggan SaaS). Semua data dibatasi per peternakan.
class Farm extends Model
{
    public const TRIAL_DAYS = 14;

    public const STATUSES = [
        'trial'     => 'Masa coba',
        'active'    => 'Berlangganan',
        'suspended' => 'Dibekukan',
    ];

    protected $fillable = ['name', 'owner_name', 'phone', 'city', 'status', 'trial_ends_at', 'active_until', 'admin_notes'];

    protected $casts = [
        'trial_ends_at' => \App\Casts\DateOnly::class,
        'active_until'  => \App\Casts\DateOnly::class,
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'owner')->oldestOfMany();
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function extendSubscription(int $months): array
    {
        $from = match (true) {
            $this->status === 'active' && $this->active_until && $this->active_until->isFuture() => $this->active_until->copy(),
            $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture()  => $this->trial_ends_at->copy(),
            default                                                                                => Carbon::today(),
        };

        $until = $from->copy()->addMonthsNoOverflow($months);

        $this->update(['status' => 'active', 'active_until' => $until->toDateString()]);

        return [$from, $until];
    }

    public function hasUnlimitedAccess(): bool
    {
        return $this->status === 'active' && $this->active_until === null;
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    // Tanggal akhir akses (null = tanpa batas)
    public function accessEndsAt(): ?Carbon
    {
        return match ($this->status) {
            'trial'  => $this->trial_ends_at,
            'active' => $this->active_until,
            default  => null,
        };
    }

    public function isAccessible(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        $end = $this->accessEndsAt();

        return $end === null || $end->gte(Carbon::today());
    }

    public function daysLeft(): ?int
    {
        $end = $this->accessEndsAt();

        return $end ? (int) Carbon::today()->diffInDays($end, false) : null;
    }

    public function blockedMessage(): string
    {
        return match (true) {
            $this->status === 'suspended' => 'Peternakan ini sedang dibekukan. Silakan hubungi admin HEFAM.',
            $this->status === 'trial'     => 'Masa coba gratis peternakan ini sudah habis. Hubungi admin HEFAM untuk berlangganan.',
            default                       => 'Masa langganan peternakan ini sudah habis. Hubungi admin HEFAM untuk memperpanjang.',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status !== 'suspended' && !$this->isAccessible()) {
            return 'Habis';
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusToneAttribute(): string
    {
        return match (true) {
            $this->status === 'suspended' || !$this->isAccessible() => 'danger',
            $this->status === 'trial'                               => 'warning',
            default                                                 => 'success',
        };
    }
}
