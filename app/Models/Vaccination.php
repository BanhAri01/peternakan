<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasFactory, BelongsToFarm, SoftDeletes, RecordsActivity;

    protected $fillable = [
        'vaccination_date',
        'coop_id',
        'age_weeks',
        'vaccine_name',
        'target_disease',
        'method',
        'dosage',
        'officer',
        'cost',
        'notes',
    ];

    protected $casts = [
        'vaccination_date' => \App\Casts\DateOnly::class,
        'cost'             => 'decimal:2',
    ];

    public function coop(): BelongsTo
    {
        return $this->belongsTo(Coop::class);
    }
}