<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Nomor WA yang boleh bertanya ke Asisten Kandang: baru dilayani setelah kode WA benar, berhenti bila pendaftarnya dihapus
class AssistantPhone extends Model
{
    use BelongsToFarm;

    protected $fillable = ['user_id', 'phone', 'label', 'verified_at', 'code_hash', 'code_expires_at', 'code_attempts'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'code_expires_at' => 'datetime', 'code_attempts' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function codePending(): bool
    {
        return !$this->isVerified() && $this->code_expires_at?->isFuture() === true;
    }

    public function ownerActive(): bool
    {
        return $this->user !== null;
    }

    public function serviceable(): bool
    {
        return $this->isVerified() && $this->ownerActive();
    }
}
