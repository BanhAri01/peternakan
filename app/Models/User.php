<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
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

    public function hasPin(): bool
    {
        return !empty($this->pin);
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