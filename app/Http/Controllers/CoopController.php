<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CoopController extends Controller
{
    public function index()
    {
        $coops = Coop::orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'empty' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        // Laporan terakhir tiap kandang
        $lastLogs = DailyLog::whereIn('id', DailyLog::selectRaw('MAX(id)')->groupBy('coop_id'))->get()->keyBy('coop_id');

        // Rata-rata produksi 7 hari terakhir
        $avgHdp = DailyLog::where('log_date', '>=', Carbon::today()->subDays(6)->toDateString())
            ->selectRaw('coop_id, AVG(hdp_percentage) as hdp')
            ->groupBy('coop_id')
            ->pluck('hdp', 'coop_id');

        return view('coops.index', compact('coops', 'lastLogs', 'avgHdp'));
    }

    public function show(Request $request, Coop $coop)
    {
        $days  = in_array((int) $request->get('days'), [14, 30, 60, 90]) ? (int) $request->get('days') : 30;
        $start = Carbon::today()->subDays($days - 1);

        $logs = DailyLog::with('recorder')
            ->where('coop_id', $coop->id)
            ->where('log_date', '>=', $start->toDateString())
            ->orderBy('log_date')
            ->get();

        $useStandard = (bool) app(FarmContext::class)->get()?->allows('strain');
        $chart = ['labels' => [], 'hdp' => [], 'standard' => [], 'eggs' => [], 'loss' => []];
        $byDate = $logs->keyBy(fn ($l) => $l->log_date->toDateString());
        for ($d = $start->copy(); $d->lte(Carbon::today()); $d->addDay()) {
            $log = $byDate->get($d->toDateString());
            $chart['labels'][] = $d->translatedFormat('d M');
            $chart['hdp'][]    = $log ? (float) $log->hdp_percentage : null;
            $chart['standard'][] = $useStandard ? $coop->standardHdp($d) : null;
            $chart['eggs'][]   = $log ? (int) $log->eggs_total_count : null;
            $chart['loss'][]   = $log ? $log->mortality + $log->cull : null;
        }

        $eggKg  = (float) $logs->sum('eggs_total_kg');
        $stats = [
            'days'      => $logs->count(),
            'eggs'      => (int) $logs->sum('eggs_total_count'),
            'egg_kg'    => $eggKg,
            'avg_hdp'   => round((float) $logs->avg('hdp_percentage'), 1),
            'avg_std'   => $useStandard ? round((float) $logs->avg(fn ($l) => $coop->standardHdp($l->log_date)), 1) : null,
            'best_hdp'  => (float) $logs->max('hdp_percentage'),
            'feed_kg'   => (float) $logs->sum('feed_consumed_kg'),
            'fcr'       => $eggKg > 0 ? round($logs->sum('feed_consumed_kg') / $eggKg, 2) : null,
            'mortality' => (int) $logs->sum('mortality'),
            'cull'      => (int) $logs->sum('cull'),
        ];

        $totalLoss    = max(0, $coop->initial_population - $coop->current_population);
        $vaccinations = $coop->vaccinations()->latest('vaccination_date')->take(10)->get();
        $recentLogs   = $logs->sortByDesc('log_date')->take(10);

        return view('coops.show', compact('coop', 'days', 'chart', 'stats', 'totalLoss', 'vaccinations', 'recentLogs', 'useStandard'));
    }

    public function create()
    {
        return view('coops.create', ['coop' => new Coop(['status' => 'active', 'initial_age_weeks' => 18])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['current_population'] = $data['initial_population'];

        if ($data['status'] === 'active' && ($blocked = $this->coopLimitResponse())) {
            return $blocked;
        }

        $coop = Coop::create($data);

        return redirect()->route('coops.index')->with('success', 'Kandang "' . $coop->name . '" berhasil ditambahkan.');
    }

    public function edit(Coop $coop)
    {
        return view('coops.edit', compact('coop'));
    }

    public function update(Request $request, Coop $coop)
    {
        $data = $request->validate($this->rules() + [
            'current_population' => 'required|integer|min:0',
        ]);

        if ($data['status'] === 'active' && $coop->status !== 'active' && ($blocked = $this->coopLimitResponse())) {
            return $blocked;
        }

        $coop->update($data);

        return redirect()->route('coops.index')->with('success', 'Data kandang "' . $coop->name . '" berhasil disimpan.');
    }

    public function destroy(Coop $coop)
    {
        // Kandang yang sudah punya riwayat tidak boleh dihapus agar laporan tidak hilang
        if ($coop->dailyLogs()->withTrashed()->exists() || $coop->vaccinations()->withTrashed()->exists()) {
            return back()->with('error', 'Kandang "' . $coop->name . '" sudah punya riwayat panen/vaksin sehingga tidak bisa dihapus. Ubah statusnya menjadi "Kosong" atau "Afkir" saja.');
        }

        $coop->delete();

        return redirect()->route('coops.index')->with('success', 'Kandang berhasil dihapus.');
    }

    private function coopLimitResponse()
    {
        $farm = app(FarmContext::class)->get();

        if ($farm && $farm->atLimit('coops', Coop::where('status', 'active')->count())) {
            return back()->withInput()->with('error', $farm->limitMessage('coops', 'kandang aktif'));
        }

        return null;
    }

    private function rules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'capacity'           => 'required|integer|min:0',
            'initial_population' => 'required|integer|min:0',
            'strain'             => 'nullable|string|max:255',
            'chick_in_date'      => 'required|date',
            'initial_age_weeks'  => 'required|integer|min:0|max:150',
            'status'             => 'required|in:active,culled,empty',
        ];
    }
}
