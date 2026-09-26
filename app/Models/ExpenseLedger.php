<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'expense_type',
        'category',
        'item_name',
        'supplier',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'payment_method',
        'officer',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity'         => 'decimal:2',
        'unit_price'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];
}