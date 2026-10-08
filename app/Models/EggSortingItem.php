<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;

class EggSortingItem extends Model
{
    use BelongsToFarm, SoftDeletes;

    protected $fillable = ['egg_sorting_id', 'egg_grade_id', 'trays_count', 'extra_eggs', 'total_eggs', 'weight_kg'];

    public function sorting()
    {
        return $this->belongsTo(EggSorting::class, 'egg_sorting_id');
    }

    public function grade()
    {
        return $this->belongsTo(EggGrade::class, 'egg_grade_id');
    }
}
