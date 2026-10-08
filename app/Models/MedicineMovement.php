<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineMovement extends Model
{
    use BelongsToFarm, SoftDeletes, RecordsActivity;

    public const DIRECTIONS = [
        'masuk' => ['label' => 'Masuk (beli)', 'tone' => 'success', 'icon' => 'bi-box-arrow-in-down'],
        'pakai' => ['label' => 'Dipakai', 'tone' => 'info', 'icon' => 'bi-droplet-half'],
        'buang' => ['label' => 'Dibuang (rusak/kedaluwarsa)', 'tone' => 'danger', 'icon' => 'bi-trash3'],
    ];

    protected $fillable = [
        'medicine_id', 'movement_date', 'direction', 'quantity', 'unit_cost', 'total_cost',
        'coop_id', 'supplier', 'expiry_date', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'movement_date' => \App\Casts\DateOnly::class,
        'expiry_date'   => \App\Casts\DateOnly::class,
        'quantity'      => 'decimal:2',
        'unit_cost'     => 'decimal:2',
        'total_cost'    => 'decimal:2',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTIONS[$this->direction]['label'] ?? $this->direction;
    }
}
