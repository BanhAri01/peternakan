<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address'];

    public function sales()
    {
        return $this->hasMany(EggSale::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Total akumulasi hutang bakul ini
    public function getTotalDebtAttribute()
    {
        return $this->sales()->sum('debt_amount');
    }

    public function getWaNumberAttribute(): ?string
    {
        return \App\Support\Format::waNumber($this->phone);
    }
}