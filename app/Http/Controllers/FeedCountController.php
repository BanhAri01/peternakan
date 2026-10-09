<?php

namespace App\Http\Controllers;

use App\Models\FeedCount;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedCountController extends Controller
{
    public function create(Request $request)
    {
        $today = Carbon::today()->toDateString();

        return view('feed-counts.create', [
            'feedStocks' => FeedStock::orderBy('feed_name')->get(),
            'counts'     => FeedCount::where('count_date', $today)->get()->keyBy('feed_stock_id'),
            'sackKg'     => Setting::num('sack_kg') ?: 50,
            'isOwner'    => $request->user()->isOwner(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'counts'                 => 'required|array|min:1',
            'counts.*.feed_stock_id' => ['required', FarmRule::exists('feed_stocks')],
            'counts.*.sacks'         => 'nullable|numeric|min:0|max:100000',
            'counts.*.extra_kg'      => 'nullable|numeric|min:0|max:1000000',
        ]);

        $lines = collect($data['counts'])->filter(fn ($l) => ($l['sacks'] ?? null) !== null || ($l['extra_kg'] ?? null) !== null);
        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'Isi jumlah karung minimal untuk satu jenis pakan.');
        }

        $sackKg = Setting::num('sack_kg') ?: 50;
        $today  = Carbon::today()->toDateString();

        $results = DB::transaction(fn () => $lines->map(function ($line) use ($sackKg, $today, $request) {
            $feed     = FeedStock::lockForUpdate()->findOrFail($line['feed_stock_id']);
            $existing = FeedCount::where('count_date', $today)->where('feed_stock_id', $feed->id)->first();
            $stock    = (float) $feed->stock_kg - (float) ($existing->difference_kg ?? 0);
            $counted  = round((float) ($line['sacks'] ?? 0) * $sackKg + (float) ($line['extra_kg'] ?? 0), 2);

            $count = FeedCount::updateOrCreate(
                ['count_date' => $today, 'feed_stock_id' => $feed->id],
                ['expected_kg' => round($stock, 2), 'counted_kg' => $counted, 'difference_kg' => round($counted - $stock, 2), 'recorded_by' => $request->user()->id]
            );

            $feed->update(['stock_kg' => $counted]);

            return [$feed, $count];
        }));

        if (!$request->user()->isOwner()) {
            return redirect()->route('feed-counts.create')->with('success', 'Terima kasih! Hasil hitung stok gudang sudah tersimpan.');
        }

        $off = $results->reject(fn ($r) => $r[1]->isBalanced());
        if ($off->isEmpty()) {
            return redirect()->route('feed-counts.index')->with('success', 'Stok gudang cocok dengan catatan aplikasi.');
        }

        return redirect()->route('feed-counts.index')->with('warning', 'Ada selisih: ' . $off->map(fn ($r) => $r[0]->feed_name . ' ' . ($r[1]->difference_kg > 0 ? '+' : '') . Format::number($r[1]->difference_kg, 1) . ' kg')->implode(', ') . '. Stok aplikasi sudah disamakan dengan hasil hitung.');
    }

    public function index(Request $request)
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m']);

        $month = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->input('bulan'))->startOfMonth() : Carbon::today()->startOfMonth();
        $end   = $month->copy()->endOfMonth()->min(Carbon::today());

        $counts = FeedCount::with('feedStock', 'recorder')->whereBetween('count_date', [$month->toDateString(), $end->toDateString()])->get()
            ->groupBy(fn ($c) => $c->count_date->toDateString());

        $usage = FeedingItem::active()->whereBetween('feedings.feed_date', [$month->toDateString(), $end->toDateString()])
            ->selectRaw('feedings.feed_date as d, SUM(feeding_items.feed_kg) as kg')->groupBy('feedings.feed_date')->pluck('kg', 'd');

        $days = collect();
        for ($day = $end->copy(); $day->gte($month); $day->subDay()) {
            $key   = $day->toDateString();
            $items = $counts[$key] ?? collect();
            $days->push([
                'date'   => $day->copy(),
                'counts' => $items,
                'used'   => (float) ($usage[$key] ?? 0),
                'status' => $items->isEmpty() ? 'none' : ($items->every->isBalanced() ? 'ok' : 'off'),
            ]);
        }

        return view('feed-counts.index', [
            'days'      => $days,
            'month'     => $month,
            'tolerance' => FeedCount::tolerance(),
            'offCount'  => $days->where('status', 'off')->count(),
        ]);
    }
}
