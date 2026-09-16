<?php

namespace App\Http\Controllers;

use App\Models\EggGrade;
use App\Models\EggPurchase;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    public function index()
    {
        $feedStocks = FeedStock::all();
        $eggGrades = EggGrade::where('is_active', true)->get();
        $suppliers = Supplier::orderBy('name')->get();

        $recentFeedPurchases = FeedPurchase::with(['supplier', 'feedStock'])->latest('purchase_date')->take(10)->get();
        $recentEggPurchases = EggPurchase::with(['supplier', 'grade'])->latest('purchase_date')->take(10)->get();

        return view('procurement.index', compact(
            'feedStocks',
            'eggGrades',
            'suppliers',
            'recentFeedPurchases',
            'recentEggPurchases'
        ));
    }

    // 1. Simpan Kulakan Telur dari Peternak Lain
    public function storeEggPurchase(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'    => 'required',
            'egg_grade_id'   => 'required|exists:egg_grades,id',
            'purchase_date'  => 'required|date',
            'unit_type'      => 'required|in:kg,krat,butir',
            'quantity_unit'  => 'required|numeric|min:0.1',
            'price_per_unit' => 'required|numeric|min:100',
            'weight_kg'      => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        if (!is_numeric($validated['supplier_id'])) {
            $supplier = Supplier::firstOrCreate([
                'name' => trim($validated['supplier_id']),
                'type' => 'egg'
            ]);
            $supplierId = $supplier->id;
        } else {
            $supplierId = $validated['supplier_id'];
        }

        EggPurchase::create([
            'supplier_id'    => $supplierId,
            'egg_grade_id'   => $validated['egg_grade_id'],
            'purchase_date'  => $validated['purchase_date'],
            'unit_type'      => $validated['unit_type'],
            'quantity_unit'  => $validated['quantity_unit'],
            'price_per_unit' => $validated['price_per_unit'],
            'weight_kg'      => $validated['weight_kg'] ?? 0,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Kulakan telur dari peternak lain berhasil ditambahkan ke stok gudang!');
    }

    // 2. Simpan Penerimaan Restock Pakan
    public function storeFeedPurchase(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'   => 'required',
            'feed_stock_id' => 'required|exists:feed_stocks,id',
            'purchase_date' => 'required|date',
            'sacks_count'   => 'nullable|integer|min:0',
            'extra_kg'      => 'nullable|numeric|min:0',
            'cost_per_kg'   => 'required|numeric|min:100',
            'notes'         => 'nullable|string|max:500',
        ]);

        $totalKg = (($validated['sacks_count'] ?? 0) * 50) + ($validated['extra_kg'] ?? 0);
        if ($totalKg <= 0) {
            return back()->withErrors(['sacks_count' => 'Jumlah pakan minimal harus lebih dari 0 kg!']);
        }

        if (!is_numeric($validated['supplier_id'])) {
            $supplier = Supplier::firstOrCreate([
                'name' => trim($validated['supplier_id']),
                'type' => 'feed'
            ]);
            $supplierId = $supplier->id;
        } else {
            $supplierId = $validated['supplier_id'];
        }

        FeedPurchase::create([
            'supplier_id'   => $supplierId,
            'feed_stock_id' => $validated['feed_stock_id'],
            'purchase_date' => $validated['purchase_date'],
            'quantity_kg'   => $totalKg,
            'cost_per_kg'   => $validated['cost_per_kg'],
            'notes'         => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Stok pakan berhasil ditambah dan harga modal rata-rata telah diperbarui!');
    }
}