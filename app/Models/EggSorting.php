<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

// Kegiatan sortir: telur campur dipilah menjadi beberapa jenis (besar, kecil, retak, dll)
class EggSorting extends Model
{
    use BelongsToFarm;

    protected $fillable = ['sort_date', 'input_count', 'input_kg', 'notes', 'recorded_by'];

    protected $casts = [
        'sort_date' => \App\Casts\DateOnly::class,
        'input_kg'  => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(EggSortingItem::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
