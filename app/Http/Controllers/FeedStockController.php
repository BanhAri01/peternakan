<?php

namespace App\Http\Controllers;

use App\Models\FeedStock;
use Illuminate\Http\Request;

class FeedStockController extends Controller
{
    public function index()
    {
        $feedStocks = FeedStock::latest()->paginate(10);
        $totalStockKg = FeedStock::sum('stock_kg');
        $totalStockValue = FeedStock::selectRaw('SUM(stock_kg * cost_per_kg) as total_value')->value('total_value') ?? 0;

        return view('feed-stocks.index', compact('feedStocks', 'totalStockKg', 'totalStockValue'));
    }

    public function create()
    {
        return view('feed-stocks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'feed_name'   => 'required|string|max:255',
            'stock_kg'    => 'required|numeric|min:0',
            'cost_per_kg' => 'required|numeric|min:0',
        ]);

        FeedStock::create($validated);

        return redirect()->route('feed-stocks.index')->with('success', 'Bahan pakan berhasil ditambahkan ke gudang!');
    }

    public function edit(FeedStock $feedStock)
    {
        return view('feed-stocks.edit', compact('feedStock'));
    }

    public function update(Request $request, FeedStock $feedStock)
    {
        $validated = $request->validate([
            'feed_name'   => 'required|string|max:255',
            'stock_kg'    => 'required|numeric|min:0',
            'cost_per_kg' => 'required|numeric|min:0',
        ]);

        $feedStock->update($validated);

        return redirect()->route('feed-stocks.index')->with('success', 'Data pakan berhasil diperbarui!');
    }

    public function destroy(FeedStock $feedStock)
    {
        $feedStock->delete();

        return redirect()->route('feed-stocks.index')->with('success', 'Data pakan berhasil dihapus dari inventaris!');
    }
}