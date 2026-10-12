<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDeposit extends Model
{
    use HasFactory, BelongsToFarm;

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'type',
        'amount',
        'balance_after',
        'notes',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
