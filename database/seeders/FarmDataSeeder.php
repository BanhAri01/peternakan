<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coop;
use App\Models\FeedStock;
use App\Models\OperationalCost;
use Carbon\Carbon;
use App\Models\EggGrade;

class FarmDataSeeder extends Seeder
{
    public function run(): void
    {

      

// Masukkan di dalam method run()
EggGrade::firstOrCreate(['name' => 'Grade Super (A)', 'code' => 'GR-A']);
EggGrade::firstOrCreate(['name' => 'Grade Sedang (B)', 'code' => 'GR-B']);
EggGrade::firstOrCreate(['name' => 'Telur Pasir / Kecil', 'code' => 'PSR']);
EggGrade::firstOrCreate(['name' => 'Bentes / Retak Rambut', 'code' => 'BNT']);
        // 1. Data Master Kandang
        Coop::firstOrCreate(
            ['name' => 'Kandang A1 (Lohmann Brown)'],
            [
                'strain' => 'Lohmann Brown',
                'chick_in_date' => Carbon::now()->subWeeks(30)->toDateString(),
                'initial_age_weeks' => 18,
                'initial_population' => 2000,
                'current_population' => 1985,
                'status' => 'active',
            ]
        );

        Coop::firstOrCreate(
            ['name' => 'Kandang B2 (Hy-Line Brown)'],
            [
                'strain' => 'Hy-Line Brown',
                'chick_in_date' => Carbon::now()->subWeeks(75)->toDateString(),
                'initial_age_weeks' => 18,
                'initial_population' => 1500,
                'current_population' => 1420,
                'status' => 'active',
            ]
        );

        // 2. Data Stok Pakan di Gudang
        FeedStock::firstOrCreate(
            ['feed_name' => 'Pakan Campur Standar (Konsentrat + Jagung + Katul)'],
            [
                'stock_kg' => 4500.00,
                'cost_per_kg' => 6800.00, // Rp 6.800/kg
            ]
        );

        FeedStock::firstOrCreate(
            ['feed_name' => 'Pakan Pabrikan Jadi (Layer Complete)'],
            [
                'stock_kg' => 2000.00,
                'cost_per_kg' => 7400.00, // Rp 7.400/kg
            ]
        );

        // 3. Biaya Operasional Rutin (Listrik & Suplemen)
        OperationalCost::firstOrCreate(
            ['description' => 'Tagihan Listrik & Pompa Air Kandang Bulan Ini'],
            [
                'expense_date' => Carbon::now()->startOfMonth()->toDateString(),
                'category' => 'Listrik',
                'amount' => 1500000.00,
            ]
        );

        
    }

    
}