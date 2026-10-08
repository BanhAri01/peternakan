<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));

        $customers = Customer::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withSum('sales as total_bought', 'total_amount')
            ->withSum('sales as total_debt', 'debt_amount')
            ->withCount('sales')
            ->withMax('sales as last_sale', 'sale_date')
            ->orderByDesc('total_debt')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totalDebt   = (float) \App\Models\EggSale::sum('debt_amount');
        $debtorCount = Customer::whereHas('sales', fn ($q) => $q->where('debt_amount', '>', 0))->count();

        return view('customers.index', compact('customers', 'search', 'totalDebt', 'debtorCount'));
    }

    public function show(Customer $customer)
    {
        $sales = $customer->sales()->with('grade')->latest('sale_date')->latest('id')->paginate(20);

        $stats = [
            'total_bought' => (float) $customer->sales()->sum('total_amount'),
            'total_paid'   => (float) $customer->sales()->sum('paid_amount'),
            'total_debt'   => (float) $customer->sales()->sum('debt_amount'),
            'total_kg'     => (float) $customer->sales()->sum('weight_kg'),
        ];

        $unpaid = $customer->sales()->where('debt_amount', '>', 0)->orderBy('sale_date')->get();

        return view('customers.show', compact('customer', 'sales', 'stats', 'unpaid'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Data pelanggan berhasil disimpan.');
    }
}
