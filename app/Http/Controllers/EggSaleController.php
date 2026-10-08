<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EggSaleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:all,unpaid,paid', 'q' => 'nullable|string|max:100']);

        $status = $request->get('status', 'all');
        $search = trim((string) $request->get('q'));

        $sales = EggSale::with(['customer', 'grade'])
            ->when($status === 'unpaid', fn ($q) => $q->where('debt_amount', '>', 0))
            ->when($status === 'paid', fn ($q) => $q->where('debt_amount', '<=', 0))
            ->when($search, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")))
            ->latest('sale_date')->latest('id')
            ->paginate(15)->withQueryString();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $grades    = EggGrade::where('is_active', true)->orderBy('id')->get();

        $today = Carbon::today()->toDateString();
        $todaySales = EggSale::where('sale_date', $today);
        $summary = [
            'today_total' => (float) (clone $todaySales)->sum('total_amount'),
            'today_kg'    => (float) (clone $todaySales)->sum('weight_kg'),
            'today_count' => (clone $todaySales)->count(),
            'debt'        => (float) EggSale::where('debt_amount', '>', 0)->sum('debt_amount'),
            'month_total' => (float) EggSale::whereBetween('sale_date', [now()->startOfMonth()->toDateString(), $today])->sum('total_amount'),
        ];

        // Harga terakhir per jenis telur & satuan, untuk mengisi otomatis kolom harga
        $lastPrices = EggSale::selectRaw('egg_grade_id, unit_type, price_per_unit')
            ->whereIn('id', EggSale::selectRaw('MAX(id)')->groupBy('egg_grade_id', 'unit_type'))
            ->get()
            ->mapWithKeys(fn ($s) => [$s->egg_grade_id . '-' . $s->unit_type => (float) $s->price_per_unit]);

        return view('sales.index', compact('sales', 'customers', 'grades', 'summary', 'status', 'search', 'lastPrices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'egg_grade_id'   => 'required|exists:egg_grades,id',
            'sale_date'      => 'required|date|before_or_equal:today',
            'unit_type'      => 'required|in:kg,krat,butir',
            'quantity_unit'  => 'required|numeric|min:0.01|max:1000000',
            'price_per_unit' => 'required|numeric|min:1|max:100000000',
            'weight_kg'      => 'nullable|numeric|min:0|max:1000000',
            'payment_mode'   => 'required|in:lunas,sebagian,tempo',
            'paid_amount'    => 'nullable|required_if:payment_mode,sebagian|numeric|min:0',
            'due_date'       => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
        ], [
            'paid_amount.required_if' => 'Isi jumlah uang yang sudah diterima.',
            'sale_date.before_or_equal' => 'Tanggal jual tidak boleh di masa depan.',
        ]);

        $customer = $this->findOrCreateCustomer($data['customer_name'], $data['customer_phone'] ?? null);
        $total    = round($data['quantity_unit'] * $data['price_per_unit'], 2);

        $paid = match ($data['payment_mode']) {
            'lunas'    => $total,
            'sebagian' => min($total, (float) $data['paid_amount']),
            default    => 0,
        };

        $dueDate = $data['due_date'] ?? null;
        if ($paid < $total && !$dueDate) {
            $dueDate = Carbon::parse($data['sale_date'])->addDays(7)->toDateString();
        }

        $sale = EggSale::create([
            'customer_id'    => $customer->id,
            'egg_grade_id'   => $data['egg_grade_id'],
            'sale_date'      => $data['sale_date'],
            'unit_type'      => $data['unit_type'],
            'quantity_unit'  => $data['quantity_unit'],
            'price_per_unit' => $data['price_per_unit'],
            'weight_kg'      => $data['unit_type'] === 'kg' ? $data['quantity_unit'] : ($data['weight_kg'] ?? 0),
            'paid_amount'    => $paid,
            'due_date'       => $paid < $total ? $dueDate : null,
            'notes'          => $data['notes'] ?? null,
        ]);

        $message = 'Penjualan ke ' . $customer->name . ' sebesar ' . Format::rupiah($sale->total_amount) . ' tersimpan.';
        if ($sale->debt_amount > 0) {
            $message .= ' Sisa tagihan ' . Format::rupiah($sale->debt_amount) . ', jatuh tempo ' . Format::date($sale->due_date) . '.';
        }

        $redirect = redirect()->route('sales.index')->with('success', $message)->with('new_sale_id', $sale->id);

        // Peringatan bila penjualan melebihi stok telur tercatat
        $stock = EggGrade::find($data['egg_grade_id'])->stock_kg;
        if ($stock <= 0) {
            $redirect->with('warning', 'Stok ' . $sale->grade->name . ' di catatan sudah habis. Pastikan panen atau kulakan sudah dicatat.');
        }

        return $redirect;
    }

    public function payDebt(Request $request, EggSale $sale)
    {
        $request->validate([
            'payment_add' => 'required|numeric|min:1|max:' . max(1, ceil($sale->debt_amount)),
        ], [
            'payment_add.max' => 'Pembayaran melebihi sisa tagihan (' . Format::rupiah($sale->debt_amount) . ').',
        ]);

        $sale->paid_amount = min($sale->total_amount, $sale->paid_amount + $request->payment_add);
        $sale->save();

        $message = $sale->debt_amount > 0
            ? 'Pembayaran ' . Format::rupiah($request->payment_add) . ' dicatat. Sisa tagihan ' . Format::rupiah($sale->debt_amount) . '.'
            : 'Tagihan ' . ($sale->customer->name ?? '') . ' sudah LUNAS.';

        return back()->with('success', $message);
    }

    public function destroy(EggSale $sale)
    {
        $sale->delete();

        return back()->with('success', 'Penjualan dihapus. Stok telur kembali seperti semula.');
    }

    private function findOrCreateCustomer(string $name, ?string $phone): Customer
    {
        $name     = trim(preg_replace('/\s+/', ' ', $name));
        $customer = Customer::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Customer::create(['name' => $name]);

        if ($phone && !$customer->phone) {
            $customer->update(['phone' => $phone]);
        }

        return $customer;
    }
}
