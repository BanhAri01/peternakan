<?php

namespace Database\Seeders;

use App\Audit\ActivityRecorder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database dengan data contoh peternakan (hanya untuk lokal/demo).
     * Seeder ini menolak berjalan di production.
     */
    public function run(): void
    {
        ActivityRecorder::withoutRecording(fn () => $this->call(DemoFarmSeeder::class));
    }
}
