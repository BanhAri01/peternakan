<?php

namespace App\Tenancy;

use App\Models\Farm;

/**
 * Peternakan yang sedang aktif selama satu request.
 * Diisi middleware SetFarmContext dari user yang login; dibaca oleh semua model BelongsToFarm.
 */
class FarmContext
{
    private ?Farm $farm = null;

    public function set(?Farm $farm): void
    {
        $this->farm = $farm;
    }

    public function get(): ?Farm
    {
        return $this->farm;
    }

    public function id(): ?int
    {
        return $this->farm?->id;
    }

    // Jalankan callback sebagai peternakan tertentu (untuk seeder, command, test)
    public function runAs(Farm $farm, callable $callback): mixed
    {
        $previous   = $this->farm;
        $this->farm = $farm;

        try {
            return $callback($farm);
        } finally {
            $this->farm = $previous;
        }
    }
}
