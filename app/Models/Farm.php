<?php

namespace App\Models;

use App\Services\Plans;
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

    protected $fillable = ['name', 'owner_name', 'phone', 'city', 'status', 'plan', 'trial_ends_at', 'active_until', 'admin_notes'];

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

    public function extendSubscription(int $months, ?string $plan = null): array
    {
        $plan = Plans::normalize($plan ?? $this->plan);
        $today = Carbon::today();
        $activeLeft = $this->status === 'active' && $this->active_until && $this->active_until->isFuture();

        $from = match (true) {
            $activeLeft && $plan !== $this->planKey() => $today->copy()->addDays($this->convertedDays($plan)),
            $activeLeft                               => $this->active_until->copy(),
            $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture() => $this->trial_ends_at->copy(),
            default                                   => $today->copy(),
        };

        $until = $from->copy()->addMonthsNoOverflow($months);

        $this->update(['status' => 'active', 'plan' => $plan, 'active_until' => $until->toDateString()]);

        return [$from, $until];
    }

    public function convertedDays(string $newPlan): int
    {
        if (!$this->active_until || !$this->active_until->isFuture()) {
            return 0;
        }

        $remaining = Carbon::today()->diffInDays($this->active_until);
        $oldPrice  = Plans::tier($this->planKey())['price'];
        $newPrice  = max(1, Plans::tier($newPlan)['price']);

        return (int) floor($remaining * $oldPrice / $newPrice);
    }

    public function planKey(): string
    {
        return Plans::normalize($this->plan);
    }

    public function planLabel(): string
    {
        return Plans::label($this->plan);
    }

    public function allows(string $feature): bool
    {
        return in_array($feature, Plans::tier($this->plan)['features'], true);
    }

    public function limit(string $key): ?int
    {
        return Plans::tier($this->plan)['limits'][$key] ?? null;
    }

    public function atLimit(string $key, int $current): bool
    {
        $limit = $this->limit($key);

        return $limit !== null && $current >= $limit;
    }

    public function limitMessage(string $key, string $what): string
    {
        return sprintf(
            'Paket %s maksimal %d %s. Naikkan paket di menu Langganan untuk menambah lagi.',
            $this->planLabel(),
            $this->limit($key),
            $what
        );
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
