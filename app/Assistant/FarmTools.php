<?php

namespace App\Assistant;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\FeedCount;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Models\Medicine;
use App\Models\Setting;
use App\Models\Vaccination;
use App\Services\EggStock;
use App\Services\FarmFinance;
use App\Services\Payroll;
use Carbon\Carbon;
use InvalidArgumentException;

class FarmTools
{
    private const MAX_RANGE_DAYS = 366;
    private const MAX_DAILY_COOPS = 8;

    public function definitions(): array
    {
        $range = [
            'dari'   => ['type' => 'string', 'description' => 'Tanggal awal YYYY-MM-DD'],
            'sampai' => ['type' => 'string', 'description' => 'Tanggal akhir YYYY-MM-DD'],
        ];

        return [
            $this->tool('info_peternakan', 'Nama peternakan, tanggal hari ini, dan daftar kandang (populasi, strain, umur minggu, status). Panggil dulu bila perlu nama kandang.', []),
            $this->tool('produksi', 'Ringkasan produksi telur per kandang dalam satu periode: jumlah butir, kg, rata-rata HDP dibanding standar strain, FCR, ayam mati/afkir. Bawaan 7 hari terakhir.', $range + [
                'kandang' => ['type' => 'string', 'description' => 'Nama kandang (opsional)'],
            ]),
            $this->tool('produksi_harian', 'Data panen per hari per kandang (butir, HDP, standar HDP, pakan kg, mati) untuk melihat tren naik/turun.', [
                'hari'    => ['type' => 'integer', 'description' => 'Jumlah hari ke belakang, 1-31, bawaan 7'],
                'kandang' => ['type' => 'string', 'description' => 'Nama kandang (opsional)'],
            ]),
            $this->tool('stok_pakan', 'Stok pakan di gudang, pemakaian rata-rata per hari, perkiraan habis berapa hari lagi, dan selisih hitung gudang 14 hari terakhir.', []),
            $this->tool('keuangan', 'Pendapatan, biaya (pakan, gaji/kas, vaksin, obat), dan laba bersih dalam satu periode. Bawaan bulan ini.', $range),
            $this->tool('piutang', 'Pelanggan yang belum lunas: total piutang, yang sudah jatuh tempo, dan tanggal nota tertua.', []),
            $this->tool('stok_telur', 'Stok telur per jenis (kg) dan telur campur yang belum disortir (butir).', []),
            $this->tool('penjualan', 'Penjualan telur per jenis dan pelanggan terbesar dalam satu periode. Bawaan bulan ini.', $range),
            $this->tool('pekerja', 'Absensi dan perkiraan gaji pekerja dalam satu bulan.', [
                'bulan' => ['type' => 'string', 'description' => 'Bulan YYYY-MM, bawaan bulan ini'],
            ]),
            $this->tool('kesehatan', 'Jadwal vaksin 14 hari ke depan, vaksin 30 hari terakhir, dan obat/vitamin yang menipis atau kedaluwarsa.', []),
        ];
    }

    public function run(string $name, array $input): array
    {
        return match ($name) {
            'info_peternakan' => $this->farmInfo(),
            'produksi'        => $this->production($input),
            'produksi_harian' => $this->dailyProduction($input),
            'stok_pakan'      => $this->feedStock(),
            'keuangan'        => $this->finance($input),
            'piutang'         => $this->receivables(),
            'stok_telur'      => $this->eggStock(),
            'penjualan'       => $this->sales($input),
            'pekerja'         => $this->workers($input),
            'kesehatan'       => $this->health(),
            default           => throw new InvalidArgumentException('Alat tidak dikenal: ' . $name),
        };
    }

    private function tool(string $name, string $description, array $properties): array
    {
        return [
            'name'        => $name,
            'description' => $description,
            'inputSchema' => $properties === [] ? ['type' => 'object'] : ['type' => 'object', 'properties' => $properties],
        ];
    }

    private function farmInfo(): array
    {
        $today = Carbon::today();

        return [
            'peternakan' => Setting::get('farm_name'),
            'hari_ini'   => $today->translatedFormat('l, d F Y'),
            'tanggal'    => $today->toDateString(),
            'kandang'    => Coop::orderBy('name')->get()->map(fn (Coop $coop) => [
                'nama'          => $coop->name,
                'status'        => $coop->status_label,
                'populasi'      => (int) $coop->current_population,
                'strain'        => $coop->strain,
                'umur_minggu'   => $coop->ageInWeeks($today),
                'standar_hdp'   => $coop->status === 'active' ? $coop->standardHdp($today) : null,
            ])->all(),
        ];
    }

    private function production(array $input): array
    {
        [$start, $end] = $this->range($input, 6);
        $coop = $this->coop($input);

        $rows = FarmFinance::coopPerformance($start, $end)
            ->when($coop, fn ($c) => $c->filter(fn ($r) => $r['coop']->id === $coop->id))
            ->map(fn ($r) => [
                'kandang'        => $r['coop']->name,
                'hari_tercatat'  => $r['days'],
                'butir'          => $r['egg_count'],
                'kg'             => round($r['egg_kg'], 1),
                'rata_hdp'       => $r['avg_hdp'],
                'standar_hdp'    => $r['coop']->status === 'active' ? $r['coop']->standardHdp($end) : null,
                'pakan_kg'       => round($r['feed_kg'], 1),
                'fcr'            => $r['fcr'],
                'mati_afkir'     => $r['loss'],
                'populasi'       => (int) $r['coop']->current_population,
            ])->values();

        return [
            'periode' => $this->period($start, $end),
            'kandang' => $rows->all(),
            'total'   => [
                'butir'      => $rows->sum('butir'),
                'kg'         => round($rows->sum('kg'), 1),
                'pakan_kg'   => round($rows->sum('pakan_kg'), 1),
                'mati_afkir' => $rows->sum('mati_afkir'),
            ],
        ];
    }

    private function dailyProduction(array $input): array
    {
        $days = max(1, min(31, (int) ($input['hari'] ?? 7)));
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $coop = $this->coop($input);

        $coops = $coop ? collect([$coop]) : Coop::where('status', 'active')->orderBy('name')->get();
        if ($coops->count() > self::MAX_DAILY_COOPS) {
            return ['pesan' => 'Kandang terlalu banyak, sebutkan satu nama kandang.', 'kandang' => $coops->pluck('name')->all()];
        }

        $logs = DailyLog::whereIn('coop_id', $coops->pluck('id'))
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('log_date')
            ->get(['coop_id', 'log_date', 'eggs_total_count', 'eggs_total_kg', 'hdp_percentage', 'feed_consumed_kg', 'mortality', 'cull'])
            ->groupBy('coop_id');

        return [
            'periode' => $this->period($start, $end),
            'kandang' => $coops->map(fn (Coop $c) => [
                'kandang' => $c->name,
                'harian'  => $logs->get($c->id, collect())->map(fn ($l) => [
                    'tanggal'     => $l->log_date->toDateString(),
                    'butir'       => (int) $l->eggs_total_count,
                    'kg'          => (float) $l->eggs_total_kg,
                    'hdp'         => (float) $l->hdp_percentage,
                    'standar_hdp' => $c->standardHdp($l->log_date),
                    'pakan_kg'    => (float) $l->feed_consumed_kg,
                    'mati'        => (int) $l->mortality,
                    'afkir'       => (int) $l->cull,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function feedStock(): array
    {
        $today = Carbon::today();
        $sackKg = Setting::num('sack_kg') ?: 50;
        $usage = FeedingItem::dailyUsageByFeed($today->copy()->subDays(7)->toDateString(), $today->copy()->subDay()->toDateString());
        $tolerance = FeedCount::tolerance();

        return [
            'berat_karung_kg' => $sackKg,
            'pakan' => FeedStock::orderBy('feed_name')->get()->map(function (FeedStock $f) use ($usage, $sackKg) {
                $perDay = round((float) ($usage[$f->id] ?? 0), 1);

                return [
                    'nama'           => $f->feed_name,
                    'stok_kg'        => (float) $f->stock_kg,
                    'stok_karung'    => round((float) $f->stock_kg / $sackKg, 1),
                    'pakai_per_hari' => $perDay,
                    'cukup_hari'     => $perDay > 0 ? (int) floor((float) $f->stock_kg / $perDay) : null,
                    'harga_per_kg'   => (float) $f->cost_per_kg,
                ];
            })->all(),
            'selisih_gudang_14_hari' => FeedCount::with('feedStock')
                ->where('count_date', '>=', $today->copy()->subDays(13)->toDateString())
                ->orderBy('count_date')
                ->get()
                ->reject(fn (FeedCount $c) => abs((float) $c->difference_kg) <= $tolerance)
                ->map(fn (FeedCount $c) => [
                    'tanggal'   => $c->count_date->toDateString(),
                    'pakan'     => $c->feedStock->feed_name ?? '-',
                    'tercatat'  => (float) $c->expected_kg,
                    'dihitung'  => (float) $c->counted_kg,
                    'selisih'   => (float) $c->difference_kg,
                ])->values()->all(),
        ];
    }

    private function finance(array $input): array
    {
        [$start, $end] = $this->range($input, null);
        $s = array_map(fn ($v) => (int) round($v), FarmFinance::summary($start->toDateString(), $end->toDateString()));

        return [
            'periode'            => $this->period($start, $end),
            'penjualan_telur'    => $s['revenue'],
            'pendapatan_lain'    => $s['other_income'],
            'total_pendapatan'   => $s['total_income'],
            'pakan_dimakan'      => $s['feed_used'],
            'kulakan_telur'      => $s['egg_bought'],
            'pengeluaran_kas'    => $s['expenses'],
            'vaksin'             => $s['vaccines'],
            'obat_dipakai'       => $s['medicine_used'],
            'total_biaya'        => $s['total_cost'],
            'laba_bersih'        => $s['net_profit'],
            'uang_masuk'         => $s['cash_in'] + $s['other_income'],
            'uang_keluar'        => $s['cash_out'],
            'piutang_baru'       => $s['new_debt'],
            'catatan'            => 'Laba memakai pakan yang dimakan; uang keluar memakai pakan yang dibeli.',
        ];
    }

    private function receivables(): array
    {
        $today = Carbon::today()->toDateString();

        $rows = EggSale::with('customer:id,name')
            ->where('debt_amount', '>', 0)
            ->selectRaw('customer_id, SUM(debt_amount) as debt, MIN(sale_date) as oldest,
                SUM(CASE WHEN due_date IS NOT NULL AND due_date <= ? THEN debt_amount ELSE 0 END) as overdue', [$today])
            ->groupBy('customer_id')
            ->orderByDesc('debt')
            ->get();

        return [
            'total_piutang'     => (int) round($rows->sum('debt')),
            'sudah_jatuh_tempo' => (int) round($rows->sum('overdue')),
            'jumlah_pelanggan'  => $rows->count(),
            'pelanggan'         => $rows->take(15)->map(fn ($r) => [
                'nama'        => $r->customer->name ?? 'Tanpa nama',
                'piutang'     => (int) round($r->debt),
                'jatuh_tempo' => (int) round($r->overdue),
                'nota_tertua' => Carbon::parse($r->oldest)->toDateString(),
            ])->values()->all(),
        ];
    }

    private function eggStock(): array
    {
        EggStock::flush();
        $stock = EggStock::kg();

        return [
            'jenis' => EggGrade::orderByDesc('is_mixed')->orderBy('name')->get()->map(fn (EggGrade $g) => [
                'nama'    => $g->name,
                'stok_kg' => $stock[$g->id] ?? 0,
                'campur'  => (bool) $g->is_mixed,
            ])->all(),
            'telur_campur_belum_disortir_butir' => EggStock::mixedEggsWaiting(),
        ];
    }

    private function sales(array $input): array
    {
        [$start, $end] = $this->range($input, null);
        $base = EggSale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]);

        $byGrade = (clone $base)->with('grade:id,name')
            ->selectRaw('egg_grade_id, SUM(weight_kg) as kg, SUM(total_amount) as total')
            ->groupBy('egg_grade_id')->orderByDesc('total')->get();

        $byCustomer = (clone $base)->with('customer:id,name')
            ->selectRaw('customer_id, SUM(weight_kg) as kg, SUM(total_amount) as total')
            ->groupBy('customer_id')->orderByDesc('total')->take(10)->get();

        return [
            'periode'     => $this->period($start, $end),
            'total_kg'    => round((float) $byGrade->sum('kg'), 1),
            'total_rupiah' => (int) round($byGrade->sum('total')),
            'per_jenis'   => $byGrade->map(fn ($r) => [
                'jenis'      => $r->grade->name ?? '-',
                'kg'         => round((float) $r->kg, 1),
                'rupiah'     => (int) round($r->total),
                'harga_rata' => $r->kg > 0 ? (int) round($r->total / $r->kg) : null,
            ])->all(),
            'pelanggan_terbesar' => $byCustomer->map(fn ($r) => [
                'nama'   => $r->customer->name ?? 'Umum',
                'kg'     => round((float) $r->kg, 1),
                'rupiah' => (int) round($r->total),
            ])->all(),
        ];
    }

    private function workers(array $input): array
    {
        $period = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($input['bulan'] ?? '')) ? $input['bulan'] : Carbon::today()->format('Y-m');

        return [
            'bulan'   => Payroll::periodLabel($period),
            'pekerja' => app(Payroll::class)->summary($period)->map(fn ($r) => [
                'nama'          => $r['worker']->name,
                'hari_kerja'    => $r['days'],
                'absensi'       => array_filter($r['counts']),
                'perkiraan_gaji' => (int) round($r['base']),
                'sudah_dibayar' => $r['paid'] !== null,
            ])->values()->all(),
        ];
    }

    private function health(): array
    {
        $today = Carbon::today();

        $vaccine = fn (Vaccination $v) => [
            'tanggal' => $v->vaccination_date->toDateString(),
            'kandang' => $v->coop->name ?? '-',
            'vaksin'  => $v->vaccine_name,
            'penyakit' => $v->target_disease,
        ];

        return [
            'vaksin_akan_datang' => Vaccination::with('coop:id,name')
                ->whereBetween('vaccination_date', [$today->toDateString(), $today->copy()->addDays(14)->toDateString()])
                ->orderBy('vaccination_date')->get()->map($vaccine)->all(),
            'vaksin_30_hari_terakhir' => Vaccination::with('coop:id,name')
                ->whereBetween('vaccination_date', [$today->copy()->subDays(30)->toDateString(), $today->copy()->subDay()->toDateString()])
                ->orderByDesc('vaccination_date')->get()->map($vaccine)->all(),
            'obat_perlu_perhatian' => Medicine::withStock()->orderBy('name')->get()
                ->map(fn (Medicine $m) => [
                    'nama'        => $m->name,
                    'stok'        => $m->stock . ' ' . $m->unit,
                    'menipis'     => $m->isLow(),
                    'kedaluwarsa' => $m->expiryStatus($today),
                    'tanggal_kedaluwarsa' => $m->expiry_date?->toDateString(),
                ])
                ->filter(fn ($m) => $m['menipis'] || $m['kedaluwarsa'] !== null)
                ->values()->all(),
        ];
    }

    private function range(array $input, ?int $defaultDays): array
    {
        $today = Carbon::today();
        $end = $this->date($input['sampai'] ?? null) ?? $today->copy();
        $start = $this->date($input['dari'] ?? null)
            ?? ($defaultDays === null ? $end->copy()->startOfMonth() : $end->copy()->subDays($defaultDays));

        if ($end->gt($today)) {
            $end = $today->copy();
        }
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            $start = $end->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        return [$start, $end];
    }

    private function date(mixed $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function coop(array $input): ?Coop
    {
        $name = trim((string) ($input['kandang'] ?? ''));
        if ($name === '') {
            return null;
        }

        $needle = mb_strtolower($name);
        $coops = Coop::orderBy('name')->get();

        return $coops->first(fn (Coop $c) => mb_strtolower($c->name) === $needle)
            ?? $coops->first(fn (Coop $c) => str_contains(mb_strtolower($c->name), $needle))
            ?? throw new InvalidArgumentException('Kandang "' . $name . '" tidak ditemukan. Kandang yang ada: ' . $coops->pluck('name')->implode(', '));
    }

    private function period(Carbon $start, Carbon $end): string
    {
        return $start->toDateString() . ' s/d ' . $end->toDateString();
    }
}
