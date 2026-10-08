<?php

namespace App\Services;

use App\Models\EggGrade;
use App\Models\Farm;
use App\Models\Setting;
use App\Models\User;
use App\Tenancy\FarmContext;
use Illuminate\Support\Facades\DB;

// Membuat peternakan baru lengkap dengan akun pemilik dan data awal yang siap pakai
class FarmProvisioner
{
    public const DEFAULT_GRADES = [
        ['name' => 'Telur Besar', 'code' => 'B'],
        ['name' => 'Telur Sedang', 'code' => 'S'],
        ['name' => 'Telur Kecil', 'code' => 'K'],
        ['name' => 'Retak / Bentes', 'code' => 'R'],
    ];

    /**
     * @param array{farm_name:string, owner_name:string, phone?:string, city?:string, email:string, password:string} $data
     */
    public function create(array $data, string $status = 'trial', ?string $activeUntil = null): User
    {
        return DB::transaction(function () use ($data, $status, $activeUntil) {
            $farm = Farm::create([
                'name'          => $data['farm_name'],
                'owner_name'    => $data['owner_name'],
                'phone'         => $data['phone'] ?? null,
                'city'          => $data['city'] ?? null,
                'status'        => $status,
                'trial_ends_at' => $status === 'trial' ? today()->addDays(Farm::TRIAL_DAYS)->toDateString() : null,
                'active_until'  => $status === 'active' ? $activeUntil : null,
            ]);

            $owner = User::create([
                'farm_id'  => $farm->id,
                'name'     => $data['owner_name'],
                'email'    => strtolower(trim($data['email'])),
                'password' => $data['password'],
                'role'     => 'owner',
            ]);

            app(FarmContext::class)->runAs($farm, function () use ($data) {
                EggGrade::mixed();
                foreach (self::DEFAULT_GRADES as $grade) {
                    EggGrade::create($grade + ['is_active' => true]);
                }

                Setting::put(array_filter([
                    'farm_name'    => $data['farm_name'],
                    'farm_owner'   => $data['owner_name'],
                    'farm_phone'   => $data['phone'] ?? null,
                    'farm_address' => $data['city'] ?? null,
                ]));
            });

            return $owner;
        });
    }
}
