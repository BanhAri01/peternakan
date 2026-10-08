<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withSum('feedPurchases as feed_total', 'total_cost')
            ->withSum('eggPurchases as egg_total', 'total_cost')
            ->orderBy('name')
            ->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:feed,egg,both',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ]);

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Data pemasok berhasil disimpan.');
    }
}
