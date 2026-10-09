<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\FeedStock;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeedStockController extends Controller
{
    public function index()
    {
        $feedStocks = FeedStock::orderBy('feed_name')->get();

        $usage = \App\Models\FeedingItem::dailyUsageByFeed(Carbon::today()->subDays(6)->toDateString(), Carbon::today()->toDateString());

        $totalStockKg    = (float) $feedStocks->sum(fn ($f) => max(0, $f->stock_kg));
        $totalStockValue = (float) $feedStocks->sum(fn ($f) => max(0, $f->stock_kg) * $f->cost_per_kg);
        $sackKg          = Setting::num('sack_kg');
        $lowDays         = Setting::num('low_feed_days');

        return view('feed-stocks.index', compact('feedStocks', 'usage', 'totalStockKg', 'totalStockValue', 'sackKg', 'lowDays'));
    }

    public function create()
    {
        return view('feed-stocks.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $feed = FeedStock::create($data);

        return redirect()->route('feed-stocks.index')->with('success', 'Jenis pakan "' . $feed->feed_name . '" berhasil ditambahkan.');
    }

    public function edit(FeedStock $feedStock)
    {
        return view('feed-stocks.edit', compact('feedStock'));
    }

    public function update(Request $request, FeedStock $feedStock)
    {
        $data = $request->validate($this->rules());

        $feedStock->update($data);

        return redirect()->route('feed-stocks.index')->with('success', 'Data pakan "' . $feedStock->feed_name . '" berhasil disimpan.');
    }

    public function destroy(FeedStock $feedStock)
    {
        // Pakan yang sudah dipakai/dibeli tidak dihapus agar riwayat biaya tetap benar
        if ($feedStock->dailyLogs()->withTrashed()->exists() || $feedStock->usages()->exists() || $feedStock->purchases()->exists()) {
            return back()->with('error', 'Pakan "' . $feedStock->feed_name . '" sudah punya riwayat pemakaian/pembelian sehingga tidak bisa dihapus.');
        }

        $feedStock->delete();

        return redirect()->route('feed-stocks.index')->with('success', 'Jenis pakan berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'feed_name'   => 'required|string|max:255',
            'stock_kg'    => 'required|numeric|min:0|max:10000000',
            'cost_per_kg' => 'required|numeric|min:0|max:1000000',
        ];
    }
}
