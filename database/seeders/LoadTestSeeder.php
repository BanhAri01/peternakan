<?php

namespace Database\Seeders;

use App\Audit\ActivityRecorder;
use App\Models\Coop;
use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\FeedStock;
use App\Services\FarmProvisioner;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoadTestSeeder extends Seeder
{
    private const CHUNK = 1000;

    private array $ids = [];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LoadTestSeeder tidak boleh dijalankan di production.');
        }

        mt_srand(7);

        $farms = (int) env('LOAD_FARMS', 20);
        $days  = (int) env('LOAD_DAYS', 1095);

        foreach (['daily_logs', 'egg_sortings', 'invoices'] as $table) {
            $this->ids[$table] = (int) DB::table($table)->max('id');
        }

        ActivityRecorder::withoutRecording(function () use ($farms, $days) {
            for ($i = 1; $i <= $farms; $i++) {
                $this->seedFarm($i, $days);
                $this->command?->info("Peternakan {$i}/{$farms} selesai");
            }
        });
    }

    private function seedFarm(int $index, int $days): void
    {
        $owner = app(FarmProvisioner::class)->create([
            'farm_name' => 'Peternakan Beban ' . $index, 'owner_name' => 'Pemilik ' . $index,
            'email' => "beban{$index}@hefam.test", 'password' => 'password',
        ], 'active');
        $farm = $owner->farm;

        app(FarmContext::class)->runAs($farm, function () use ($farm, $owner, $days) {
            $start  = Carbon::today()->subDays($days - 1);
            $mixed  = EggGrade::mixed();
            $grades = collect(['Besar', 'Sedang', 'Kecil'])->map(fn ($n) => EggGrade::create(['name' => 'Telur ' . $n, 'is_active' => true]));
            $coops  = collect(range(1, 4))->map(fn ($n) => Coop::create([
                'name' => 'Kandang ' . $n, 'capacity' => 3000, 'initial_population' => 2500, 'current_population' => 2500,
                'chick_in_date' => $start->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
            ]));
            $feed      = FeedStock::create(['feed_name' => 'Pakan Layer', 'stock_kg' => 5000, 'cost_per_kg' => 7400]);
            $customers = collect(range(1, 30))->map(fn ($n) => Customer::create(['name' => 'Bakul ' . $n, 'phone' => '0812' . str_pad($n, 8, '0')]));

            $rows = array_fill_keys(['daily_logs', 'daily_log_grades', 'egg_sortings', 'egg_sorting_items', 'invoices', 'egg_sales', 'expense_ledgers', 'feed_purchases'], []);
            $now  = now()->toDateTimeString();
            $base = ['farm_id' => $farm->id, 'created_at' => $now, 'updated_at' => $now];

            for ($d = 0; $d < $days; $d++) {
                $date = $start->copy()->addDays($d)->toDateString();
                $dayEggs = 0;

                foreach ($coops as $coop) {
                    $eggs = (int) (2500 * mt_rand(80, 92) / 100);
                    $kg   = round($eggs * 0.062, 2);
                    $feedKg = 290 + mt_rand(0, 20);
                    $logId = ++$this->ids['daily_logs'];
                    $dayEggs += $eggs;

                    $rows['daily_logs'][] = $base + [
                        'id' => $logId, 'coop_id' => $coop->id, 'log_date' => $date, 'mortality' => mt_rand(0, 2), 'cull' => 0,
                        'feed_stock_id' => $feed->id, 'feed_consumed_kg' => $feedKg, 'feed_cost_total' => $feedKg * 7400,
                        'eggs_total_count' => $eggs, 'eggs_total_kg' => $kg, 'hdp_percentage' => round($eggs / 25, 2),
                        'fcr' => round($feedKg / $kg, 2), 'recorded_by' => $owner->id,
                    ];
                    $rows['daily_log_grades'][] = $base + [
                        'daily_log_id' => $logId, 'egg_grade_id' => $mixed->id, 'trays_count' => intdiv($eggs, 30),
                        'extra_eggs' => $eggs % 30, 'total_eggs' => $eggs, 'weight_kg' => $kg,
                    ];
                }

                $sortId = ++$this->ids['egg_sortings'];
                $rows['egg_sortings'][] = $base + ['id' => $sortId, 'sort_date' => $date, 'input_count' => $dayEggs, 'input_kg' => round($dayEggs * 0.062, 2), 'recorded_by' => $owner->id];
                foreach ($grades as $g => $grade) {
                    $part = (int) ($dayEggs * [0.5, 0.35, 0.15][$g]);
                    $rows['egg_sorting_items'][] = $base + [
                        'egg_sorting_id' => $sortId, 'egg_grade_id' => $grade->id, 'trays_count' => intdiv($part, 30),
                        'extra_eggs' => $part % 30, 'total_eggs' => $part, 'weight_kg' => round($part * 0.062, 2),
                    ];
                }

                for ($n = 1; $n <= 6; $n++) {
                    $invoiceId = ++$this->ids['invoices'];
                    $customer  = $customers[mt_rand(0, 29)];
                    $unpaid    = $d > $days - 30 && mt_rand(1, 4) === 1;
                    $due       = $unpaid ? Carbon::parse($date)->addDays(7)->toDateString() : null;

                    $rows['invoices'][] = $base + [
                        'id' => $invoiceId, 'number' => 'NT-' . Carbon::parse($date)->format('Ymd') . '-' . $n,
                        'customer_id' => $customer->id, 'sale_date' => $date, 'due_date' => $due, 'created_by' => $owner->id,
                    ];

                    foreach ([0, 1] as $line) {
                        $kg    = mt_rand(20, 60);
                        $total = $kg * 26000;
                        $rows['egg_sales'][] = $base + [
                            'invoice_id' => $invoiceId, 'customer_id' => $customer->id, 'egg_grade_id' => $grades[$line]->id,
                            'sale_date' => $date, 'unit_type' => 'kg', 'quantity_unit' => $kg, 'price_per_unit' => 26000,
                            'weight_kg' => $kg, 'trays_count' => 0, 'price_per_kg' => 26000, 'total_amount' => $total,
                            'paid_amount' => $unpaid ? 0 : $total, 'debt_amount' => $unpaid ? $total : 0,
                            'payment_status' => $unpaid ? 'unpaid' : 'paid', 'due_date' => $due,
                        ];
                    }
                }

                if ($d % 3 === 0) {
                    $rows['expense_ledgers'][] = $base + [
                        'transaction_date' => $date, 'expense_type' => 'Operasional', 'category' => ['Listrik', 'Gaji', 'Obat', 'Transport'][mt_rand(0, 3)],
                        'item_name' => 'Biaya rutin', 'quantity' => 1, 'unit' => 'kali', 'unit_price' => 150000, 'total_amount' => 150000,
                        'payment_method' => 'Tunai', 'officer' => 'Pemilik',
                    ];
                }

                if ($d % 7 === 0) {
                    $rows['feed_purchases'][] = $base + [
                        'feed_stock_id' => $feed->id, 'purchase_date' => $date, 'quantity_kg' => 8000, 'cost_per_kg' => 7400, 'total_cost' => 8000 * 7400,
                    ];
                }

                if ($d % 60 === 59 || $d === $days - 1) {
                    $this->flush($rows);
                }
            }
        });
    }

    private function flush(array &$rows): void
    {
        foreach ($rows as $table => $items) {
            foreach (array_chunk($items, self::CHUNK) as $chunk) {
                DB::table($table)->insert($chunk);
            }
            $rows[$table] = [];
        }
    }
}
