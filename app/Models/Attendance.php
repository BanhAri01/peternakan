<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use App\Tenancy\FarmScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class Attendance extends Model
{
    use BelongsToFarm;

    public const STATUSES = [
        'hadir'    => ['label' => 'Hadir', 'days' => 1.0, 'tone' => 'success', 'icon' => 'bi-check-circle-fill', 'short' => 'H'],
        'setengah' => ['label' => 'Setengah hari', 'days' => 0.5, 'tone' => 'warning', 'icon' => 'bi-circle-half', 'short' => '½'],
        'izin'     => ['label' => 'Izin', 'days' => 0.0, 'tone' => 'info', 'icon' => 'bi-envelope-fill', 'short' => 'I'],
        'sakit'    => ['label' => 'Sakit', 'days' => 0.0, 'tone' => 'info', 'icon' => 'bi-bandaid-fill', 'short' => 'S'],
        'libur'    => ['label' => 'Libur', 'days' => 0.0, 'tone' => 'neutral', 'icon' => 'bi-moon-fill', 'short' => 'L'],
        'alpa'     => ['label' => 'Tidak masuk', 'days' => 0.0, 'tone' => 'danger', 'icon' => 'bi-x-circle-fill', 'short' => 'A'],
    ];

    protected $fillable = ['user_id', 'work_date', 'status', 'source', 'notes'];

    protected $casts = [
        'work_date' => \App\Casts\DateOnly::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function markPresent(User $user): void
    {
        if (!$user->isWorker() || !$user->farm_id) {
            return;
        }

        $attendance = static::withoutGlobalScope(FarmScope::class)->firstOrNew([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        if ($attendance->exists) {
            return;
        }

        $attendance->farm_id = $user->farm_id;
        $attendance->status  = 'hadir';
        $attendance->source  = 'otomatis';

        try {
            $attendance->save();
        } catch (UniqueConstraintViolationException) {
        }
    }
}
