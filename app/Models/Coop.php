<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coop extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity;

    public const STATUSES = [
        'active' => 'Aktif (sedang produksi)',
        'empty'  => 'Kosong',
        'culled' => 'Sudah afkir',
    ];

    protected $fillable = [
        'name',
        'capacity',
        'initial_population',
        'current_population',
        'strain',
        'chick_in_date',
        'initial_age_weeks',
        'status',
    ];

    protected $casts = [
        'chick_in_date' => \App\Casts\DateOnly::class,
    ];

    public function dailyLogs()
    {
        return $this->hasMany(DailyLog::class);
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }

    // Umur ayam (minggu) pada tanggal tertentu
    public function ageInWeeks($date = null): int
    {
        if (!$this->chick_in_date) {
            return (int) $this->initial_age_weeks;
        }

        $at    = $date ? Carbon::parse($date) : Carbon::today();
        $weeks = (int) floor(max(0, $this->chick_in_date->diffInDays($at, false)) / 7);

        return (int) $this->initial_age_weeks + $weeks;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'empty'  => 'Kosong',
            'culled' => 'Afkir',
            default  => ucfirst((string) $this->status),
        };
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'culled' => 'danger',
            default  => 'neutral',
        };
    }

    // Persentase isi kandang terhadap kapasitas
    public function getOccupancyAttribute(): ?float
    {
        return $this->capacity > 0 ? round($this->current_population / $this->capacity * 100, 1) : null;
    }
}
