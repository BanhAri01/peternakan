<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database dengan data contoh peternakan (hanya untuk lokal/demo).
     * Seeder ini menolak berjalan di production.
     */
    public function run(): void
    {
        $this->call(DemoFarmSeeder::class);
    }
}
