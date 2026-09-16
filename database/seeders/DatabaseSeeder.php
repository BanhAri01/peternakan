<?php

namespace Database\Seeders;

use App\Models\Coop;
use App\Models\EggGrade;
use App\Models\FeedStock;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Autentikasi (Owner & Pekerja Lapangan)
        User::create([
            'name'     => 'Owner Peternakan',
            'role'     => 'owner',
            'email'    => 'owner@farm.com',
            'password' => Hash::make('password123'),
        ]);

        User::create(['name' => 'Wayan', 'role' => 'worker']);
        User::create(['name' => 'Made',  'role' => 'worker']);
        User::create(['name' => 'Ketut', 'role' => 'worker']);

        // 2. Kategori Master Grade Telur
        EggGrade::create(['name' => 'Grade Super (A)', 'code' => 'GR-A', 'is_active' => true]);
        EggGrade::create(['name' => 'Grade Sedang (B)', 'code' => 'GR-B', 'is_active' => true]);
        EggGrade::create(['name' => 'Telur Pasir/Dara', 'code' => 'PSR',  'is_active' => true]);
        EggGrade::create(['name' => 'Bentes / Retak',  'code' => 'BNT',  'is_active' => true]);

        // 3. Stok Bahan Pakan Awal di Gudang
        FeedStock::create([
            'feed_name'   => 'Konsentrat Layer 36%',
            'stock_kg'    => 2500,
            'cost_per_kg' => 9200,
        ]);
        FeedStock::create([
            'feed_name'   => 'Jagung Giling Pipil',
            'stock_kg'    => 4000,
            'cost_per_kg' => 5800,
        ]);
        FeedStock::create([
            'feed_name'   => 'Bekatul / Dedak Halus',
            'stock_kg'    => 1500,
            'cost_per_kg' => 3800,
        ]);

        // 4. Data Kandang Layer Aktif
        Coop::create([
            'name'               => 'Kandang A1 (Muda)',
            'capacity'           => 3000,
            'initial_population' => 2850,
            'current_population' => 2850,
            'strain'             => 'Lohmann Brown',
            'chick_in_date'      => Carbon::now()->subWeeks(10)->toDateString(),
            'initial_age_weeks'  => 18, // Umur saat masuk
            'status'             => 'active',
        ]);

        Coop::create([
            'name'               => 'Kandang B2 (Produksi Puncak)',
            'capacity'           => 3000,
            'initial_population' => 2900,
            'current_population' => 2880,
            'strain'             => 'Isa Brown',
            'chick_in_date'      => Carbon::now()->subWeeks(25)->toDateString(),
            'initial_age_weeks'  => 18,
            'status'             => 'active',
        ]);

        Coop::create([
            'name'               => 'Kandang C3 (Kandang Tua)',
            'capacity'           => 2500,
            'initial_population' => 2400,
            'current_population' => 2310,
            'strain'             => 'Novogen Brown',
            'chick_in_date'      => Carbon::now()->subWeeks(62)->toDateString(),
            'initial_age_weeks'  => 18,
            'status'             => 'active',
        ]);

        // 5. Data Rekanan Supplier & Pelanggan / Bakul Telur
        Supplier::create([
            'name'    => 'UD Pakan Makmur',
            'type'    => 'feed',
            'phone'   => '081234567890',
            'address' => 'Jl. Raya Industri Pakan No. 12',
        ]);

        Supplier::create([
            'name'    => 'Pak Nyoman (Peternak Sebelah)',
            'type'    => 'egg',
            'phone'   => '081987654321',
            'address' => 'Banjar Kawan',
        ]);

        Customer::create([
            'name'    => 'Toko Sembako Berkah',
            'phone'   => '081223344556',
            'address' => 'Pasar Induk Kios No. 4',
        ]);

        Customer::create([
            'name'    => 'Bakul Pak Agus',
            'phone'   => '087899887766',
            'address' => 'Jl. Kenanga No. 8',
        ]);
        
        $this->call([
            FarmDataSeeder::class,
        ]);

    }
}