<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\Device;
use App\Models\DailyLog;
use App\Models\DailyLogGrade;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\EggSortingItem;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Models\User;
use App\Services\EggStock;
use App\Services\FarmFinance;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['date' => 'nullable|date']);

        $date = $request->date('date') ?? Carbon::today();
        if ($date->isFuture()) {
            $date = Carbon::today();
        }
        $day       = $date->toDateString();
        $yesterday = $date->copy()->subDay()->toDateString();

        $mixedWaiting = EggStock::mixedEggsWaiting();
        $hdpWarn     = Setting::num('hdp_warning');
        $lowFeedDays = Setting::num('low_feed_days');
        $eggPrice    = Setting::num('egg_price_per_kg');

        // ---------------- Data hari terpilih ----------------
        $logs = DailyLog::with(['coop', 'feedStock', 'grades.grade', 'recorder'])->where('log_date', $day)->get()->keyBy('coop_id');
        $yesterdayLogs = DailyLog::where('log_date', $yesterday)->get()->keyBy('coop_id');

        $activeCoops = Coop::where('status', 'active')->orderBy('name')->get();
        $population  = (int) $activeCoops->sum('current_population');

        $eggCount  = (int) $logs->sum('eggs_total_count');
        $eggKg     = (float) $logs->sum('eggs_total_kg');
        $feedKg    = (float) $logs->sum('feed_consumed_kg');
        $feedCost  = (float) $logs->sum('feed_cost_total');
        $mortality = (int) $logs->sum('mortality');
        $cull      = (int) $logs->sum('cull');

        // HDP farm = total telur ÷ jumlah ayam di kandang yang sudah dicatat (populasi saat dicatat)
        $loggedPopulation = $logs->sum(fn ($l) => $l->hdp_percentage > 0
            ? $l->eggs_total_count / ($l->hdp_percentage / 100)
            : ($l->coop->current_population ?? 0));
        $hdp = $loggedPopulation > 0 ? round($eggCount / $loggedPopulation * 100, 1) : 0;
        $fcr = $eggKg > 0 ? round($feedKg / $eggKg, 2) : null;

        // Bandingkan dengan kemarin hanya untuk kandang yang sudah dicatat hari ini
        $yesterdayEggs = (int) $yesterdayLogs->only($logs->keys()->all())->sum('eggs_total_count');

        // Modal per kg telur = (pakan hari ini + rata-rata biaya lain per hari) ÷ kg telur
        $overhead = FarmFinance::dailyOverhead($day);
        $hppPerKg = $eggKg > 0 ? round(($feedCost + $overhead) / $eggKg) : 0;

        // Harga jual rata-rata 30 hari terakhir (pakai harga acuan jika belum ada penjualan)
        $avgPrice = (float) EggSale::whereBetween('sale_date', [$date->copy()->subDays(29)->toDateString(), $day])
            ->where('weight_kg', '>', 0)
            ->selectRaw('SUM(total_amount) / SUM(weight_kg) as p')->value('p');
        $priceSource = $avgPrice > 0 ? 'rata-rata penjualan 30 hari' : 'harga acuan di Pengaturan';
        $avgPrice    = $avgPrice > 0 ? $avgPrice : $eggPrice;

        $salesToday = (float) EggSale::where('sale_date', $day)->sum('total_amount');
        $totalDebt  = (float) EggSale::where('debt_amount', '>', 0)->sum('debt_amount');

        // ---------------- Kartu per kandang ----------------
        $coopCards = $activeCoops->map(function ($coop) use ($logs, $yesterdayLogs, $day, $hdpWarn, $avgPrice) {
            $log  = $logs->get($coop->id);
            $prev = $yesterdayLogs->get($coop->id);
            $pop  = $coop->current_population + ($log ? $log->mortality + $log->cull : 0);

            $status = ['tone' => 'neutral', 'text' => 'Belum dicatat'];
            if ($log) {
                $status = ['tone' => 'success', 'text' => 'Normal'];
                if ($log->hdp_percentage < $hdpWarn) {
                    $status = ['tone' => 'danger', 'text' => 'Produksi rendah'];
                } elseif ($prev && ($prev->hdp_percentage - $log->hdp_percentage) >= 5) {
                    $status = ['tone' => 'warning', 'text' => 'Produksi turun'];
                }
            }

            $revenue = $log ? $log->eggs_total_kg * $avgPrice : 0;
            $margin  = $log ? $revenue - $log->feed_cost_total : 0;

            return [
                'coop'       => $coop,
                'log'        => $log,
                'prev'       => $prev,
                'age'        => $coop->ageInWeeks($day),
                'status'     => $status,
                'gramPerHen' => $log && $pop > 0 ? round($log->feed_consumed_kg * 1000 / $pop) : null,
                'revenue'    => $revenue,
                'margin'     => $margin,
            ];
        });

        // ---------------- Stok pakan ----------------
        // Pemakaian per jenis pakan 7 hari terakhir
        $usageByFeed = DailyLog::whereBetween('log_date', [$date->copy()->subDays(6)->toDateString(), $day])
            ->selectRaw('feed_stock_id, SUM(feed_consumed_kg) / 7 as per_day')
            ->groupBy('feed_stock_id')->pluck('per_day', 'feed_stock_id');

        $feeds = FeedStock::orderBy('feed_name')->get()->map(function ($feed) use ($usageByFeed) {
            $perDay = (float) ($usageByFeed[$feed->id] ?? 0);

            return [
                'feed'      => $feed,
                'per_day'   => $perDay,
                'days_left' => $perDay > 0 ? (int) floor(max(0, $feed->stock_kg) / $perDay) : null,
            ];
        });

        // ---------------- Hal yang perlu diperhatikan ----------------
        $alerts = [];

        if ($date->isToday()) {
            $missing = $activeCoops->reject(fn ($c) => $logs->has($c->id));
            if ($missing->isNotEmpty()) {
                $alerts[] = ['tone' => 'info', 'icon' => 'bi-clipboard-x', 'title' => $missing->count() . ' kandang belum dicatat hari ini',
                    'text' => $missing->pluck('name')->join(', '), 'url' => route('daily-logs.create'), 'cta' => 'Catat sekarang'];
            }
        }

        foreach ($coopCards as $card) {
            if (!$card['log']) {
                continue;
            }
            if ($card['status']['tone'] === 'danger') {
                $alerts[] = ['tone' => 'danger', 'icon' => 'bi-graph-down-arrow', 'title' => $card['coop']->name . ': produksi rendah (' . Format::number($card['log']->hdp_percentage, 1) . '%)',
                    'text' => 'Di bawah batas ' . Format::number($hdpWarn) . '%. Periksa pakan, air minum, dan kesehatan ayam.', 'url' => route('coops.show', $card['coop']), 'cta' => 'Lihat kandang'];
            } elseif ($card['status']['tone'] === 'warning') {
                $alerts[] = ['tone' => 'warning', 'icon' => 'bi-arrow-down-right', 'title' => $card['coop']->name . ': produksi turun dibanding kemarin',
                    'text' => 'Dari ' . Format::number($card['prev']->hdp_percentage, 1) . '% menjadi ' . Format::number($card['log']->hdp_percentage, 1) . '%.', 'url' => route('coops.show', $card['coop']), 'cta' => 'Lihat kandang'];
            }

            $loss = $card['log']->mortality + $card['log']->cull;
            if ($loss > 0 && $card['coop']->current_population > 0 && $loss / ($card['coop']->current_population + $loss) >= 0.005) {
                $alerts[] = ['tone' => 'danger', 'icon' => 'bi-heartbreak-fill', 'title' => $card['coop']->name . ': ' . $loss . ' ekor mati/afkir',
                    'text' => 'Angka kematian cukup tinggi. Pertimbangkan memanggil dokter hewan.', 'url' => route('coops.show', $card['coop']), 'cta' => 'Lihat kandang'];
            }
        }

        foreach ($feeds as $f) {
            if ($f['feed']->stock_kg < 0) {
                $alerts[] = ['tone' => 'danger', 'icon' => 'bi-box-seam', 'title' => 'Stok ' . $f['feed']->feed_name . ' minus',
                    'text' => 'Pakan dipakai melebihi stok tercatat. Catat pembelian pakan yang belum dimasukkan.', 'url' => route('procurement.index'), 'cta' => 'Catat pembelian'];
            } elseif ($f['days_left'] !== null && $f['days_left'] <= $lowFeedDays) {
                $alerts[] = ['tone' => 'warning', 'icon' => 'bi-box-seam', 'title' => 'Pakan ' . $f['feed']->feed_name . ' tinggal ±' . $f['days_left'] . ' hari',
                    'text' => 'Sisa ' . Format::number($f['feed']->stock_kg) . ' kg. Segera pesan pakan.', 'url' => route('procurement.index'), 'cta' => 'Catat pembelian'];
            }
        }

        if ($mixedWaiting > 0) {
            $alerts[] = ['tone' => 'info', 'icon' => 'bi-funnel', 'title' => Format::number($mixedWaiting) . ' butir telur campur belum disortir',
                'text' => 'Sekitar ' . Format::trays($mixedWaiting) . '. Sortir agar stok tiap jenis telur akurat.', 'url' => route('sortings.create'), 'cta' => 'Sortir sekarang'];
        }

        $overdue = EggSale::with('customer')->where('debt_amount', '>', 0)->whereNotNull('due_date')->where('due_date', '<', $day)->get();
        if ($overdue->isNotEmpty()) {
            $alerts[] = ['tone' => 'warning', 'icon' => 'bi-alarm', 'title' => $overdue->count() . ' tagihan sudah lewat jatuh tempo',
                'text' => 'Total ' . Format::rupiah($overdue->sum('debt_amount')) . ' dari ' . $overdue->pluck('customer.name')->unique()->join(', '), 'url' => route('customers.index'), 'cta' => 'Lihat piutang'];
        }

        // ---------------- Grafik 14 hari ----------------
        $trendStart = $date->copy()->subDays(13);
        $trend = DailyLog::whereBetween('log_date', [$trendStart->toDateString(), $day])
            ->selectRaw('log_date, SUM(eggs_total_count) as eggs, SUM(eggs_total_kg) as kg, SUM(feed_consumed_kg) as feed, AVG(hdp_percentage) as hdp')
            ->groupBy('log_date')->get()
            ->keyBy(fn ($r) => Carbon::parse($r->log_date)->toDateString());

        $chart = ['labels' => [], 'eggs' => [], 'hdp' => [], 'feed' => []];
        for ($d = $trendStart->copy(); $d->lte($date); $d->addDay()) {
            $row = $trend->get($d->toDateString());
            $chart['labels'][] = $d->translatedFormat('d M');
            $chart['eggs'][]   = $row ? (float) $row->kg : null;
            $chart['hdp'][]    = $row ? round((float) $row->hdp, 1) : null;
            $chart['feed'][]   = $row ? (float) $row->feed : null;
        }

        // ---------------- Komposisi jenis telur ----------------
        // Hasil sortir pada tanggal ini (+ rincian jenis dari laporan panen lama)
        $sortedToday = EggSortingItem::query()
            ->join('egg_sortings', 'egg_sortings.id', '=', 'egg_sorting_items.egg_sorting_id')
            ->join('egg_grades', 'egg_grades.id', '=', 'egg_sorting_items.egg_grade_id')
            ->where('egg_sortings.sort_date', $day)
            ->selectRaw('egg_grades.name, SUM(egg_sorting_items.total_eggs) as eggs, SUM(egg_sorting_items.weight_kg) as kg')
            ->groupBy('egg_grades.name')
            ->get();
        $legacy = DailyLogGrade::query()
            ->join('daily_logs', 'daily_logs.id', '=', 'daily_log_grades.daily_log_id')
            ->join('egg_grades', 'egg_grades.id', '=', 'daily_log_grades.egg_grade_id')
            ->where('daily_logs.log_date', $day)
            ->where('egg_grades.is_mixed', false)
            ->selectRaw('egg_grades.name, SUM(daily_log_grades.total_eggs) as eggs, SUM(daily_log_grades.weight_kg) as kg')
            ->groupBy('egg_grades.name')
            ->get();
        $gradeMix = $sortedToday->concat($legacy)
            ->groupBy('name')
            ->map(fn ($rows, $name) => (object) ['name' => $name, 'eggs' => $rows->sum('eggs'), 'kg' => (float) $rows->sum('kg')])
            ->sortByDesc('kg')
            ->values();

        // Stok telur di gudang per jenis
        $stockKg    = EggStock::kg();
        $eggStocks  = EggGrade::where('is_active', true)->orderByDesc('is_mixed')->orderBy('id')->get()
            ->map(fn ($g) => ['grade' => $g, 'kg' => $stockKg[$g->id] ?? 0]);

        // ---------------- Keuangan bulan berjalan ----------------
        $month = FarmFinance::summary($date->copy()->startOfMonth()->toDateString(), $day);

        // Langkah awal untuk peternakan baru
        $onboarding = [
            ['done' => $activeCoops->isNotEmpty(), 'title' => 'Tambah kandang', 'text' => 'Isi nama kandang, jumlah ayam, dan umur ayam.', 'url' => route('coops.create'), 'icon' => 'bi-house-add-fill'],
            ['done' => FeedStock::exists(), 'title' => 'Tambah jenis pakan', 'text' => 'Isi stok pakan dan harga per kg.', 'url' => route('feed-stocks.create'), 'icon' => 'bi-box-seam-fill'],
            ['done' => User::ofCurrentFarm()->where('role', 'worker')->whereNotNull('pin')->exists(), 'title' => 'Tambah pekerja + PIN', 'text' => 'Pekerja masuk dengan nama dan PIN, tanpa email.', 'url' => route('users.create'), 'icon' => 'bi-person-plus-fill'],
            ['done' => Device::where('is_active', true)->exists(), 'title' => 'Daftarkan HP kandang', 'text' => 'Buka aplikasi di HP pekerja lalu tekan "Jadikan HP kandang".', 'url' => route('devices.index'), 'icon' => 'bi-phone-fill'],
            ['done' => DailyLog::exists(), 'title' => 'Catat panen pertama', 'text' => 'Catat telur, pakan, dan ayam mati hari ini.', 'url' => route('daily-logs.create'), 'icon' => 'bi-clipboard2-check-fill'],
        ];
        $onboardingLeft = collect($onboarding)->where('done', false)->count();

        return view('dashboard.index', compact('onboarding', 'onboardingLeft',
            'date', 'population', 'eggCount', 'eggKg', 'feedKg', 'feedCost', 'mortality', 'cull', 'hdp', 'fcr',
            'yesterdayEggs', 'hppPerKg', 'avgPrice', 'priceSource', 'salesToday', 'totalDebt', 'coopCards',
            'feeds', 'alerts', 'chart', 'gradeMix', 'eggStocks', 'mixedWaiting', 'month', 'hdpWarn', 'logs', 'activeCoops'
        ));
    }
}
