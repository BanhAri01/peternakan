<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'phone', 'address'];

    public function feedPurchases()
    {
        return $this->hasMany(FeedPurchase::class);
    }

    public function eggPurchases()
    {
        return $this->hasMany(EggPurchase::class);
    }
}