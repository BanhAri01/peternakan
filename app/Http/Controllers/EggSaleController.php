<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Services\EggStock;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EggSaleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:all,unpaid,paid', 'q' => 'nullable|string|max:100']);

        $status = $request->get('status', 'all');
        $search = trim((string) $request->get('q'));

        $invoices = Invoice::withTotals()
            ->with(['customer', 'lines.grade'])
            ->when($status === 'unpaid', fn ($q) => $q->whereHas('lines', fn ($l) => $l->where('debt_amount', '>', 0)))
            ->when($status === 'paid', fn ($q) => $q->whereDoesntHave('lines', fn ($l) => $l->where('debt_amount', '>', 0)))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))))
            ->latest('sale_date')->latest('id')
            ->paginate(15)->withQueryString();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $grades    = EggGrade::where('is_active', true)->orderByDesc('is_mixed')->orderBy('id')->get();
        $stock     = EggStock::kg();

        $today      = Carbon::today()->toDateString();
        $todaySales = EggSale::where('sale_date', $today);
        $summary = [
            'today_total' => (float) (clone $todaySales)->sum('total_amount'),
            'today_kg'    => (float) (clone $todaySales)->sum('weight_kg'),
            'today_count' => Invoice::where('sale_date', $today)->count(),
            'debt'        => (float) EggSale::where('debt_amount', '>', 0)->sum('debt_amount'),
            'month_total' => (float) EggSale::whereBetween('sale_date', [now()->startOfMonth()->toDateString(), $today])->sum('total_amount'),
        ];

        // Harga terakhir per jenis telur & satuan, untuk mengisi otomatis kolom harga
        $lastPrices = EggSale::selectRaw('egg_grade_id, unit_type, price_per_unit')
            ->whereIn('id', EggSale::selectRaw('MAX(id)')->groupBy('egg_grade_id', 'unit_type'))
            ->get()
            ->mapWithKeys(fn ($s) => [$s->egg_grade_id . '-' . $s->unit_type => (float) $s->price_per_unit]);

        return view('sales.index', compact('invoices', 'customers', 'grades', 'stock', 'summary', 'status', 'search', 'lastPrices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'          => 'required|string|max:255',
            'customer_phone'         => 'nullable|string|max:30',
            'sale_date'              => 'required|date|before_or_equal:today',
            'lines'                  => 'required|array|min:1|max:20',
            'lines.*.egg_grade_id'   => 'required|exists:egg_grades,id',
            'lines.*.unit_type'      => 'required|in:kg,krat,butir',
            'lines.*.quantity_unit'  => 'required|numeric|min:0.01|max:1000000',
            'lines.*.price_per_unit' => 'required|numeric|min:1|max:100000000',
            'lines.*.weight_kg'      => 'nullable|numeric|min:0|max:1000000',
            'payment_mode'           => 'required|in:lunas,sebagian,tempo',
            'paid_amount'            => 'nullable|required_if:payment_mode,sebagian|numeric|min:0',
            'due_date'               => 'nullable|date',
            'notes'                  => 'nullable|string|max:500',
        ], [
            'paid_amount.required_if'      => 'Isi jumlah uang yang sudah diterima.',
            'sale_date.before_or_equal'    => 'Tanggal jual tidak boleh di masa depan.',
            'lines.required'               => 'Tambahkan minimal satu baris telur.',
            'lines.*.quantity_unit.required' => 'Jumlah telur pada setiap baris wajib diisi.',
            'lines.*.price_per_unit.required' => 'Harga pada setiap baris wajib diisi.',
        ]);

        $customer = $this->findOrCreateCustomer($data['customer_name'], $data['customer_phone'] ?? null);
        $total    = collect($data['lines'])->sum(fn ($l) => round($l['quantity_unit'] * $l['price_per_unit'], 2));

        $paid = match ($data['payment_mode']) {
            'lunas'    => $total,
            'sebagian' => min($total, (float) $data['paid_amount']),
            default    => 0,
        };

        $dueDate = null;
        if ($paid < $total) {
            $dueDate = $data['due_date'] ?? Carbon::parse($data['sale_date'])->addDays(7)->toDateString();
        }

        $invoice = DB::transaction(function () use ($data, $customer, $paid, $dueDate, $request) {
            $invoice = Invoice::create([
                'number'      => Invoice::nextNumber($data['sale_date']),
                'customer_id' => $customer->id,
                'sale_date'   => $data['sale_date'],
                'due_date'    => $dueDate,
                'notes'       => $data['notes'] ?? null,
                'created_by'  => $request->user()->id,
            ]);

            // Uang yang diterima dibagikan ke baris-baris nota berurutan
            $remaining = $paid;
            foreach ($data['lines'] as $line) {
                $lineTotal = round($line['quantity_unit'] * $line['price_per_unit'], 2);
                $linePaid  = min($remaining, $lineTotal);
                $remaining -= $linePaid;

                $invoice->lines()->create([
                    'customer_id'    => $customer->id,
                    'egg_grade_id'   => $line['egg_grade_id'],
                    'sale_date'      => $data['sale_date'],
                    'unit_type'      => $line['unit_type'],
                    'quantity_unit'  => $line['quantity_unit'],
                    'price_per_unit' => $line['price_per_unit'],
                    'weight_kg'      => $line['unit_type'] === 'kg' ? $line['quantity_unit'] : ($line['weight_kg'] ?? 0),
                    'paid_amount'    => $linePaid,
                    'due_date'       => $linePaid < $lineTotal ? $dueDate : null,
                ]);
            }

            return $invoice;
        });

        $message = 'Nota ' . $invoice->number . ' untuk ' . $customer->name . ' sebesar ' . Format::rupiah($total) . ' tersimpan.';
        if ($paid < $total) {
            $message .= ' Sisa tagihan ' . Format::rupiah($total - $paid) . ', jatuh tempo ' . Format::date($dueDate) . '.';
        }

        $redirect = redirect()->route('sales.index')->with('success', $message)->with('new_invoice_id', $invoice->id);

        // Peringatan bila penjualan melebihi stok telur tercatat
        EggStock::flush();
        $stock = EggStock::kg();
        $short = $invoice->lines()->with('grade')->get()
            ->filter(fn ($l) => ($stock[$l->egg_grade_id] ?? 0) < 0)
            ->pluck('grade.name')->unique();
        if ($short->isNotEmpty()) {
            $redirect->with('warning', 'Stok ' . $short->join(', ') . ' di catatan sudah minus. Pastikan panen, sortir, atau kulakan sudah dicatat.');
        }

        return $redirect;
    }

    public function payDebt(Request $request, Invoice $invoice)
    {
        $debt = $invoice->debt;

        $request->validate([
            'payment_add' => 'required|numeric|min:1|max:' . max(1, ceil($debt)),
        ], [
            'payment_add.max' => 'Pembayaran melebihi sisa tagihan (' . Format::rupiah($debt) . ').',
        ]);

        $invoice->applyPayment(min($debt, (float) $request->payment_add));
        $left = $invoice->fresh()->debt;

        $message = $left > 0
            ? 'Pembayaran ' . Format::rupiah($request->payment_add) . ' dicatat. Sisa tagihan nota ' . $invoice->number . ': ' . Format::rupiah($left) . '.'
            : 'Nota ' . $invoice->number . ' (' . ($invoice->customer->name ?? '') . ') sudah LUNAS.';

        return back()->with('success', $message);
    }

    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            $invoice->lines()->delete();
            $invoice->delete();
        });

        return back()->with('success', 'Nota ' . $invoice->number . ' dihapus. Stok telur kembali seperti semula.');
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
