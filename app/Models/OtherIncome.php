<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherIncome extends Model
{
    use BelongsToFarm, SoftDeletes, RecordsActivity;

    public const CATEGORIES = [
        'ayam_afkir' => ['label' => 'Ayam afkir', 'unit' => 'ekor', 'icon' => 'bi-tag-fill'],
        'kotoran'    => ['label' => 'Kotoran / pupuk', 'unit' => 'karung', 'icon' => 'bi-flower1'],
        'karung'     => ['label' => 'Karung bekas', 'unit' => 'lembar', 'icon' => 'bi-bag'],
        'rak'        => ['label' => 'Rak telur bekas', 'unit' => 'buah', 'icon' => 'bi-grid-3x3'],
        'lainnya'    => ['label' => 'Lainnya', 'unit' => 'kali', 'icon' => 'bi-three-dots'],
    ];

    protected $fillable = [
        'income_date', 'category', 'item_name', 'quantity', 'unit', 'unit_price',
        'total_amount', 'buyer', 'coop_id', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'income_date'  => \App\Casts\DateOnly::class,
        'quantity'     => 'decimal:2',
        'unit_price'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? $this->category;
    }
}
