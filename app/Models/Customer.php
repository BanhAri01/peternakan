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

    // Total akumulasi hutang bakul ini
    public function getTotalDebtAttribute()
    {
        return $this->sales()->sum('debt_amount');
    }
}