<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity;

    protected $fillable = ['name', 'phone', 'address'];

    private ?float $debtTotal = null;

    public function sales()
    {
        return $this->hasMany(EggSale::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Total akumulasi hutang bakul ini
    public function getTotalDebtAttribute($value)
    {
        if (array_key_exists('total_debt', $this->attributes)) {
            return (float) $value;
        }

        return $this->debtTotal ??= (float) $this->sales()->sum('debt_amount');
    }

    public function getWaNumberAttribute(): ?string
    {
        return \App\Support\Format::waNumber($this->phone);
    }
}