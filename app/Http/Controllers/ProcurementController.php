<?php

namespace App\Http\Controllers;

use App\Tenancy\FarmRule;
use App\Models\EggGrade;
use App\Models\EggPurchase;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Models\Supplier;
use App\Support\Format;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab') === 'telur' ? 'telur' : 'pakan';

        $filter = $request->validate([
            'dari'  => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'pakan' => ['nullable', FarmRule::exists('feed_stocks')],
        ], [
            'sampai.after_or_equal' => 'Tanggal "sampai" tidak boleh sebelum tanggal "dari".',
        ]);

        $feedStocks = FeedStock::orderBy('feed_name')->get();
        $eggGrades  = EggGrade::where('is_active', true)->orderBy('id')->get();
        $suppliers  = Supplier::orderBy('name')->get();
        $sackKg     = Setting::num('sack_kg');

        $feedQuery = FeedPurchase::query()
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('purchase_date', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('purchase_date', '<=', $d))
            ->when($filter['pakan'] ?? null, fn ($q, $id) => $q->where('feed_stock_id', $id));

        $feedFiltered = array_filter($filter) !== [];
        $feedSummary  = $feedFiltered
            ? (clone $feedQuery)->toBase()->selectRaw('COUNT(*) as times, COALESCE(SUM(quantity_kg), 0) as kg, COALESCE(SUM(total_cost), 0) as cost')->first()
            : null;

        $feedPurchases = $feedQuery->with(['supplier', 'feedStock'])->latest('purchase_date')->latest('id')->paginate(10, ['*'], 'feed_page')->withQueryString();
        $eggPurchases  = EggPurchase::with(['supplier', 'grade'])->latest('purchase_date')->latest('id')->paginate(10, ['*'], 'egg_page')->withQueryString();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthFeed  = (float) FeedPurchase::where('purchase_date', '>=', $monthStart)->sum('total_cost');
        $monthEgg   = (float) EggPurchase::where('purchase_date', '>=', $monthStart)->sum('total_cost');

        return view('procurement.index', compact(
            'tab', 'feedStocks', 'eggGrades', 'suppliers', 'sackKg', 'feedPurchases', 'eggPurchases', 'monthFeed', 'monthEgg', 'filter', 'feedFiltered', 'feedSummary'
        ));
    }

    // Kulakan telur dari peternak lain (menambah stok telur tanpa menambah ayam)
    public function storeEggPurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_name'  => 'required|string|max:255',
            'egg_grade_id'   => ['required', FarmRule::exists('egg_grades')],
            'purchase_date'  => 'required|date|before_or_equal:today',
            'unit_type'      => 'required|in:kg,krat,butir',
            'quantity_unit'  => 'required|numeric|min:0.01',
            'price_per_unit' => 'required|numeric|min:1',
            'weight_kg'      => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        $purchase = EggPurchase::create([
            'supplier_id'    => $this->supplierId($data['supplier_name'], 'egg'),
            'egg_grade_id'   => $data['egg_grade_id'],
            'purchase_date'  => $data['purchase_date'],
            'unit_type'      => $data['unit_type'],
            'quantity_unit'  => $data['quantity_unit'],
            'price_per_unit' => $data['price_per_unit'],
            'weight_kg'      => $data['weight_kg'] ?? 0,
            'notes'          => $data['notes'] ?? null,
        ]);

        return redirect()->route('procurement.index', ['tab' => 'telur'])
            ->with('success', 'Kulakan telur ' . Format::number($purchase->weight_kg, 1) . ' kg (' . Format::rupiah($purchase->total_cost) . ') masuk ke stok.');
    }

    // Pembelian pakan: menambah stok & memperbarui harga modal rata-rata
    public function storeFeedPurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'feed_stock_id' => ['required', FarmRule::exists('feed_stocks')],
            'purchase_date' => 'required|date|before_or_equal:today',
            'sacks_count'   => 'nullable|integer|min:0',
            'extra_kg'      => 'nullable|numeric|min:0',
            'cost_per_kg'   => 'required|numeric|min:1',
            'notes'         => 'nullable|string|max:500',
        ]);

        $totalKg = (($data['sacks_count'] ?? 0) * Setting::num('sack_kg')) + ($data['extra_kg'] ?? 0);
        if ($totalKg <= 0) {
            return back()->withInput()->withErrors(['sacks_count' => 'Isi jumlah karung atau kg pakan yang dibeli.']);
        }

        $purchase = FeedPurchase::create([
            'supplier_id'   => $this->supplierId($data['supplier_name'], 'feed'),
            'feed_stock_id' => $data['feed_stock_id'],
            'purchase_date' => $data['purchase_date'],
            'quantity_kg'   => $totalKg,
            'cost_per_kg'   => $data['cost_per_kg'],
            'notes'         => $data['notes'] ?? null,
        ]);

        $feed = $purchase->feedStock->fresh();

        return redirect()->route('procurement.index')
            ->with('success', Format::number($totalKg) . ' kg ' . $feed->feed_name . ' masuk gudang. Stok sekarang ' . Format::number($feed->stock_kg) . ' kg, harga modal rata-rata ' . Format::rupiah($feed->cost_per_kg) . '/kg.');
    }

    private function supplierId(string $name, string $type): int
    {
        $name     = trim(preg_replace('/\s+/', ' ', $name));
        $supplier = Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if (!$supplier) {
            return Supplier::create(['name' => $name, 'type' => $type])->id;
        }

        if ($supplier->type !== $type && $supplier->type !== 'both') {
            $supplier->update(['type' => 'both']);
        }

        return $supplier->id;
    }
}
