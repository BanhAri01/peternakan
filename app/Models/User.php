<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Notifications\ResetPasswordNotification;
use App\Tenancy\FarmContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Pengguna: superadmin (admin HEFAM), owner (pemilik peternakan), worker (pekerja kandang).
 *
 * Sengaja TIDAK memakai scope peternakan otomatis, karena login dan sesi harus bisa
 * mencari user sebelum peternakan diketahui. Gunakan scopeOfCurrentFarm() di halaman peternakan.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, RecordsActivity;

    protected array $auditIgnore = ['remember_token'];

    protected array $auditMask = ['password', 'pin'];

    protected $fillable = [
        'farm_id',
        'name',
        'email',
        'password',
        'pin',
        'role',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pin'      => 'hashed',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    // Hanya pengguna milik peternakan yang sedang aktif
    public function scopeOfCurrentFarm($query)
    {
        $farmId = app(FarmContext::class)->id();

        return $farmId === null ? $query->whereRaw('1 = 0') : $query->where('farm_id', $farmId);
    }

    // /users/{user} hanya bisa membuka pengguna dari peternakan sendiri
    public function resolveRouteBinding($value, $field = null)
    {
        return static::ofCurrentFarm()->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    public function hasPin(): bool
    {
        return !empty($this->pin);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isWorker(): bool
    {
        return $this->role === 'worker';
    }
}
