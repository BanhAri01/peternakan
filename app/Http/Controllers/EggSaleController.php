<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use Illuminate\Http\Request;

class EggSaleController extends Controller
{
    public function index()
    {
        $sales = EggSale::with(['customer', 'grade'])->latest('sale_date')->paginate(15);
        $customers = Customer::orderBy('name')->get();
        // Ambil semua grade dan stok masing-masing
        $grades = EggGrade::where('is_active', true)->get();
        $totalOutstandingDebt = EggSale::where('payment_status', '!=', 'paid')->sum('debt_amount');

        return view('sales.index', compact('sales', 'customers', 'grades', 'totalOutstandingDebt'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'required',
            'egg_grade_id'   => 'required|exists:egg_grades,id',
            'sale_date'      => 'required|date',
            'unit_type'      => 'required|in:kg,krat,butir',
            'quantity_unit'  => 'required|numeric|min:0.1',
            'price_per_unit' => 'required|numeric|min:100',
            'paid_amount'    => 'nullable|numeric|min:0',
            'due_date'       => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        if (!is_numeric($validated['customer_id'])) {
            $customer = Customer::firstOrCreate(['name' => trim($validated['customer_id'])]);
            $customerId = $customer->id;
        } else {
            $customerId = $validated['customer_id'];
        }

        EggSale::create([
            'customer_id'    => $customerId,
            'egg_grade_id'   => $validated['egg_grade_id'],
            'sale_date'      => $validated['sale_date'],
            'unit_type'      => $validated['unit_type'],
            'quantity_unit'  => $validated['quantity_unit'],
            'price_per_unit' => $validated['price_per_unit'],
            'weight_kg'      => 0,
            'paid_amount'    => $validated['paid_amount'] ?? 0,
            'due_date'       => $validated['due_date'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return redirect()->route('sales.index')->with('success', 'Penjualan berhasil dicatat!');
    }

    public function payDebt(Request $request, EggSale $sale)
    {
        $request->validate(['payment_add' => 'required|numeric|min:1']);
        $newPaid = $sale->paid_amount + $request->payment_add;
        $sale->paid_amount = min($sale->total_amount, $newPaid);
        $sale->save();

        return back()->with('success', 'Setoran cicilan/pelunasan berhasil dicatat!');
    }
}