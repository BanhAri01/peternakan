<?php

namespace Database\Seeders;

use App\Models\Coop;
use App\Models\Customer;
use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\EggSorting;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vaccination;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Data contoh peternakan ayam petelur (60 hari) untuk demo & uji tampilan.
 * Jalankan HANYA di database kosong:  php artisan migrate:fresh --seed --seeder=DemoFarmSeeder
 */
class DemoFarmSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoFarmSeeder tidak boleh dijalankan di production.');
        }

        mt_srand(42);

        Setting::put([
            'farm_name'    => 'Sinar Abadi Farm',
            'farm_owner'   => 'Bapak Ketut',
            'farm_address' => 'Banjar Dinas Kawan, Bangli, Bali',
            'farm_phone'   => '081234567890',
        ]);

        User::create(['name' => 'Bapak Ketut', 'role' => 'owner', 'email' => 'demo@hefam.id', 'password' => 'password']);
        $workers = collect(['Wayan', 'Made', 'Komang'])->map(fn ($n) => User::create(['name' => $n, 'role' => 'worker', 'pin' => '1234']));

        $grades = collect([
            ['name' => 'Telur Besar', 'code' => 'B'],
            ['name' => 'Telur Sedang', 'code' => 'S'],
            ['name' => 'Telur Kecil', 'code' => 'K'],
            ['name' => 'Retak / Bentes', 'code' => 'R'],
        ])->map(fn ($g) => EggGrade::create($g + ['is_active' => true]));
        $mixedGrade = EggGrade::mixed();

        $feeds = collect([
            ['feed_name' => 'Pakan Layer Komplit', 'stock_kg' => 0, 'cost_per_kg' => 7400],
            ['feed_name' => 'Jagung Giling', 'stock_kg' => 0, 'cost_per_kg' => 5800],
        ])->map(fn ($f) => FeedStock::create($f));

        $feedSupplier = Supplier::create(['name' => 'UD Pakan Makmur', 'type' => 'feed', 'phone' => '081311112222', 'address' => 'Jl. Raya Bangli No. 12']);
        $eggSupplier  = Supplier::create(['name' => 'Pak Nyoman (peternak tetangga)', 'type' => 'egg', 'phone' => '081933334444']);

        $customers = collect([
            ['name' => 'Toko Sembako Berkah', 'phone' => '081223344556', 'address' => 'Pasar Kidul Kios 4'],
            ['name' => 'Bakul Bu Sari', 'phone' => '087899887766', 'address' => 'Banjar Tengah'],
            ['name' => 'Warung Men Gede', 'phone' => '085712341234'],
            ['name' => 'Pengepul Pak Agus', 'phone' => '081377778888', 'address' => 'Gianyar'],
        ])->map(fn ($c) => Customer::create($c));

        $start = Carbon::today()->subDays(59);
        $feedIndex = [];

        $coops = collect([
            ['name' => 'Kandang A', 'strain' => 'Lohmann Brown', 'capacity' => 3000, 'pop' => 2850, 'age' => 24, 'feed' => 0],
            ['name' => 'Kandang B', 'strain' => 'Isa Brown', 'capacity' => 3000, 'pop' => 2900, 'age' => 40, 'feed' => 0],
            ['name' => 'Kandang C', 'strain' => 'Hy-Line Brown', 'capacity' => 2500, 'pop' => 2400, 'age' => 70, 'feed' => 1],
        ])->map(function ($c) use ($start, &$feedIndex) {
            $coop = Coop::create([
                'name'               => $c['name'],
                'capacity'           => $c['capacity'],
                'initial_population' => $c['pop'],
                'current_population' => $c['pop'],
                'strain'             => $c['strain'],
                'chick_in_date'      => $start->copy()->subWeeks($c['age'] - 18)->toDateString(),
                'initial_age_weeks'  => 18,
                'status'             => 'active',
            ]);
            $feedIndex[$coop->id] = $c['feed'];

            return $coop;
        });

        // Stok awal pakan (pembelian pertama)
        foreach ($feeds as $feed) {
            FeedPurchase::create([
                'supplier_id' => $feedSupplier->id, 'feed_stock_id' => $feed->id, 'purchase_date' => $start->toDateString(),
                'quantity_kg' => 9000, 'cost_per_kg' => $feed->cost_per_kg,
            ]);
        }

        $mix = [0.52, 0.33, 0.11, 0.04]; // komposisi besar/sedang/kecil/retak
        $kgPerEgg = [0.066, 0.059, 0.052, 0.058];

        for ($day = 0; $day < 60; $day++) {
            $date = $start->copy()->addDays($day);
            $daySorted = []; // hasil sortir hari ini per jenis

            foreach ($coops as $coop) {
                // Hari ini sengaja dibiarkan 1 kandang belum dicatat
                if ($date->isToday() && $coop->name === 'Kandang C') {
                    continue;
                }

                $coop->refresh();
                $age = $coop->ageInWeeks($date);

                // Kurva produksi ayam petelur: puncak ±94% umur 28-35 minggu lalu turun pelan
                $base = $age < 22 ? 60 + ($age - 18) * 8 : ($age <= 35 ? 94 : max(70, 94 - ($age - 35) * 0.45));
                $hdpTarget = $base + mt_rand(-25, 25) / 10;
                if ($coop->name === 'Kandang B' && $day >= 56) {
                    $hdpTarget -= 7; // simulasi produksi turun beberapa hari terakhir
                }

                $mortality = mt_rand(0, 100) < 35 ? mt_rand(1, 3) : 0;
                $cull      = mt_rand(0, 100) < 8 ? mt_rand(1, 4) : 0;
                $pop       = $coop->current_population;
                $eggs      = (int) round($pop * $hdpTarget / 100);
                $feed      = $feeds[$feedIndex[$coop->id]];
                $feedKg    = round($pop * (mt_rand(108, 118) / 1000));

                $gradeRows = [];
                $left = $eggs;
                foreach ($grades as $i => $grade) {
                    $count = $i === count($grades) - 1 ? $left : (int) round($eggs * $mix[$i]);
                    $left -= $count;
                    $gradeRows[] = ['count' => $count, 'kg' => round($count * $kgPerEgg[$i], 2)];
                    $daySorted[$i]['count'] = ($daySorted[$i]['count'] ?? 0) + $count;
                    $daySorted[$i]['kg']    = ($daySorted[$i]['kg'] ?? 0) + round($count * $kgPerEgg[$i], 2);
                }
                $eggKg = array_sum(array_column($gradeRows, 'kg'));

                $log = DailyLog::create([
                    'coop_id'          => $coop->id,
                    'log_date'         => $date->toDateString(),
                    'mortality'        => $mortality,
                    'cull'             => $cull,
                    'feed_stock_id'    => $feed->id,
                    'feed_consumed_kg' => $feedKg,
                    'feed_cost_total'  => round($feedKg * $feed->fresh()->cost_per_kg, 2),
                    'eggs_total_count' => $eggs,
                    'eggs_total_kg'    => $eggKg,
                    'hdp_percentage'   => round($eggs / $pop * 100, 2),
                    'fcr'              => round($feedKg / $eggKg, 2),
                    'notes'            => $coop->name === 'Kandang B' && $day === 57 ? 'Ayam terlihat lesu, nafsu makan turun.' : null,
                    'recorded_by'      => $workers[$day % 3]->id,
                ]);
                // Pekerja mencatat telur campur saja
                $log->grades()->create([
                    'egg_grade_id' => $mixedGrade->id,
                    'trays_count'  => intdiv($eggs, 30),
                    'extra_eggs'   => $eggs % 30,
                    'total_eggs'   => $eggs,
                    'weight_kg'    => $eggKg,
                ]);

                $coop->decrement('current_population', $mortality + $cull);
                $feed->decrement('stock_kg', $feedKg);
            }

            // Sortir di gudang tiap sore (hari ini sengaja belum disortir)
            if (!$date->isToday()) {
                $sorting = EggSorting::create([
                    'sort_date'   => $date->toDateString(),
                    'input_count' => array_sum(array_column($daySorted, 'count')),
                    'input_kg'    => round(array_sum(array_column($daySorted, 'kg')), 2),
                    'recorded_by' => $workers[($day + 1) % 3]->id,
                ]);
                foreach ($grades as $gi => $grade) {
                    $sorting->items()->create([
                        'egg_grade_id' => $grade->id,
                        'trays_count'  => intdiv($daySorted[$gi]['count'], 30),
                        'extra_eggs'   => $daySorted[$gi]['count'] % 30,
                        'total_eggs'   => $daySorted[$gi]['count'],
                        'weight_kg'    => round($daySorted[$gi]['kg'], 2),
                    ]);
                }
            }

            // Penjualan: hampir semua telur hasil sortir terjual ke 2 pelanggan
            if (!$date->isToday()) {
                $prices = [27000, 25500, 23000, 15000];
                foreach ($grades as $gi => $grade) {
                    $kg = floor(($daySorted[$gi]['kg'] ?? 0) * mt_rand(92, 99) / 100);
                    if ($kg <= 0) {
                        continue;
                    }

                    foreach ($customers->random(2)->values() as $ci => $customer) {
                        $qty    = $ci === 0 ? floor($kg * 0.6) : $kg - floor($kg * 0.6);
                        $unpaid = mt_rand(0, 100) < 12 && $day > 45;
                        $price  = $prices[$gi] + mt_rand(-3, 3) * 100;

                        EggSale::create([
                            'customer_id'    => $customer->id,
                            'egg_grade_id'   => $grade->id,
                            'sale_date'      => $date->toDateString(),
                            'unit_type'      => 'kg',
                            'quantity_unit'  => $qty,
                            'price_per_unit' => $price,
                            'weight_kg'      => 0,
                            'paid_amount'    => $unpaid ? 0 : $qty * $price,
                            'due_date'       => $unpaid ? $date->copy()->addDays(7)->toDateString() : null,
                        ]);
                    }
                }
            }

            // Pembelian pakan tiap 10 hari
            if ($day > 0 && $day % 10 === 0) {
                foreach ($feeds as $i => $feed) {
                    FeedPurchase::create([
                        'supplier_id' => $feedSupplier->id, 'feed_stock_id' => $feed->id, 'purchase_date' => $date->toDateString(),
                        'quantity_kg' => $i === 0 ? 6000 : 3500, 'cost_per_kg' => $feed->fresh()->cost_per_kg + mt_rand(-1, 3) * 50,
                    ]);
                }
            }

            // Kulakan telur sesekali
            if ($day % 15 === 7) {
                EggPurchase::create([
                    'supplier_id' => $eggSupplier->id, 'egg_grade_id' => $grades[1]->id, 'purchase_date' => $date->toDateString(),
                    'unit_type' => 'krat', 'quantity_unit' => 40, 'price_per_unit' => 52000, 'weight_kg' => 0,
                ]);
            }
        }

        // Pengeluaran rutin
        foreach ([1, 31] as $offset) {
            $d = $start->copy()->addDays($offset)->toDateString();
            ExpenseLedger::create(['transaction_date' => $d, 'expense_type' => 'Operasional', 'category' => 'Tenaga Kerja & Gaji', 'item_name' => 'Gaji 3 pekerja kandang', 'quantity' => 3, 'unit' => 'Orang', 'unit_price' => 2200000, 'total_amount' => 6600000, 'payment_method' => 'Tunai / Kas Kecil', 'officer' => 'Bapak Ketut']);
            ExpenseLedger::create(['transaction_date' => $d, 'expense_type' => 'Operasional', 'category' => 'Listrik & Utilitas Air', 'item_name' => 'Listrik & pompa air', 'supplier' => 'PLN', 'quantity' => 1, 'unit' => 'Bulan', 'unit_price' => 1450000, 'total_amount' => 1450000, 'payment_method' => 'Transfer Bank BRI', 'officer' => 'Bapak Ketut']);
        }
        foreach ([5, 19, 33, 47] as $offset) {
            ExpenseLedger::create(['transaction_date' => $start->copy()->addDays($offset)->toDateString(), 'expense_type' => 'Operasional', 'category' => 'Tray / Krat & Pengemasan Telur', 'item_name' => 'Tray telur', 'supplier' => 'Toko Plastik Jaya', 'quantity' => 500, 'unit' => 'Pcs', 'unit_price' => 900, 'total_amount' => 450000, 'payment_method' => 'Tunai / Kas Kecil', 'officer' => 'Made']);
        }
        ExpenseLedger::create(['transaction_date' => $start->copy()->addDays(22)->toDateString(), 'expense_type' => 'Operasional', 'category' => 'Obat, Vitamin & Vaksinasi', 'item_name' => 'Vitamin air minum', 'supplier' => 'Poultry Shop Bangli', 'quantity' => 10, 'unit' => 'Botol', 'unit_price' => 65000, 'total_amount' => 650000, 'payment_method' => 'Tunai / Kas Kecil', 'officer' => 'Wayan']);
        ExpenseLedger::create(['transaction_date' => $start->copy()->addDays(40)->toDateString(), 'expense_type' => 'Non-Operasional', 'category' => 'Perbaikan & Servis Mesin/Kandang', 'item_name' => 'Ganti pipa nipple minum', 'quantity' => 1, 'unit' => 'Paket', 'unit_price' => 850000, 'total_amount' => 850000, 'payment_method' => 'Tunai / Kas Kecil', 'officer' => 'Komang']);

        // Vaksinasi
        foreach ($coops as $i => $coop) {
            $d = $start->copy()->addDays(12 + $i * 9);
            Vaccination::create(['vaccination_date' => $d->toDateString(), 'coop_id' => $coop->id, 'age_weeks' => $coop->ageInWeeks($d), 'vaccine_name' => 'ND-IB Live', 'target_disease' => 'ND, IB', 'method' => 'Air Minum', 'dosage' => '1 dosis / ekor', 'officer' => 'Wayan', 'cost' => 420000]);
        }
    }
}
