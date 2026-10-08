<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use App\Tenancy\FarmScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Mengingat HP kandang. Setelah pemilik mendaftarkan HP sekali, pekerja di HP itu
 * cukup menekan namanya lalu mengetik PIN — tanpa email dan kata sandi.
 */
class DeviceService
{
    public const COOKIE = 'hefam_device';

    private const COOKIE_MINUTES = 60 * 24 * 365 * 5; // 5 tahun

    // Perangkat terdaftar untuk browser ini (lintas peternakan, karena belum login)
    public function fromRequest(Request $request): ?Device
    {
        $uuid = $request->cookie(self::COOKIE);

        if (!is_string($uuid) || !Str::isUuid($uuid)) {
            return null;
        }

        return Device::withoutGlobalScope(FarmScope::class)
            ->with('farm')
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->first();
    }

    // Daftarkan browser ini sebagai HP kandang milik peternakan si pemilik
    public function register(Request $request, User $owner, string $name): Device
    {
        $device = $this->fromRequest($request);

        if (!$device || $device->farm_id !== $owner->farm_id) {
            $device = new Device(['uuid' => (string) Str::uuid()]);
            $device->farm_id = $owner->farm_id;
        }

        $device->fill([
            'name'         => $name,
            'is_active'    => true,
            'user_agent'   => Str::limit((string) $request->userAgent(), 250, ''),
            'last_seen_at' => now(),
            'created_by'   => $owner->id,
        ])->save();

        Cookie::queue(self::COOKIE, $device->uuid, self::COOKIE_MINUTES);

        return $device;
    }

    public function touch(Device $device): void
    {
        $device->forceFill(['last_seen_at' => now()])->saveQuietly();
    }
}
