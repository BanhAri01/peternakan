<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\WhatsApp;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search  = trim((string) $request->get('q'));
        $broker  = trim((string) $request->get('dibawa'));
        $brokers = Customer::broughtByNames();

        $customers = Customer::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($broker !== '', fn ($q) => $q->where('brought_by', $broker))
            ->withSum('sales as total_bought', 'total_amount')
            ->withSum('sales as total_debt', 'debt_amount')
            ->withCount('invoices')
            ->withMax('sales as last_sale', 'sale_date')
            ->orderByDesc('total_debt')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totalDebt   = (float) \App\Models\EggSale::sum('debt_amount');
        $debtorCount = Customer::whereHas('sales', fn ($q) => $q->where('debt_amount', '>', 0))->count();

        return view('customers.index', compact('customers', 'search', 'broker', 'brokers', 'totalDebt', 'debtorCount'));
    }

    public function show(Customer $customer)
    {
        $invoices = $customer->invoices()->withTotals()->with('lines.grade')->latest('sale_date')->latest('id')->paginate(20);

        $stats = [
            'total_bought' => (float) $customer->sales()->sum('total_amount'),
            'total_paid'   => (float) $customer->sales()->sum('paid_amount'),
            'total_debt'   => (float) $customer->sales()->sum('debt_amount'),
            'total_kg'     => (float) $customer->sales()->sum('weight_kg'),
        ];

        $unpaid = $customer->invoices()->withTotals()
            ->whereHas('lines', fn ($q) => $q->where('debt_amount', '>', 0))
            ->orderBy('sale_date')->get();

        $deposits = $customer->deposits()->with('invoice')->latest()->limit(10)->get();

        return view('customers.show', compact('customer', 'invoices', 'stats', 'unpaid', 'deposits'));
    }

    public function brokers(Request $request)
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->get('bulan')) ? $request->get('bulan') : now()->format('Y-m');
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $inMonth = fn ($q) => $q->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]);

        $customers = Customer::whereNotNull('brought_by')
            ->withCount(['invoices as invoice_count' => $inMonth])
            ->withSum(['sales as sold_kg' => $inMonth], 'weight_kg')
            ->withSum(['sales as sold_total' => $inMonth], 'total_amount')
            ->withSum(['sales as sold_paid' => $inMonth], 'paid_amount')
            ->withSum(['sales as sold_debt' => $inMonth], 'debt_amount')
            ->orderBy('name')
            ->get();

        $groups = $customers->groupBy('brought_by')->map(fn ($rows, $name) => [
            'name'      => $name,
            'customers' => $rows,
            'invoices'  => (int) $rows->sum('invoice_count'),
            'kg'        => (float) $rows->sum('sold_kg'),
            'total'     => (float) $rows->sum('sold_total'),
            'paid'      => (float) $rows->sum('sold_paid'),
            'debt'      => (float) $rows->sum('sold_debt'),
        ])->sortBy('name')->values();

        return view('customers.brokers', compact('groups', 'month', 'start'));
    }

    public function edit(Customer $customer)
    {
        $brokers = Customer::broughtByNames();

        return view('customers.edit', compact('customer', 'brokers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:30',
            'address'    => 'nullable|string|max:500',
            'brought_by' => 'nullable|string|max:100',
        ]);

        $data['brought_by'] = trim((string) ($data['brought_by'] ?? '')) ?: null;

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Data pelanggan berhasil disimpan.');
    }

    public function sendDebtWa(Customer $customer, WhatsApp $whatsApp)
    {
        $phone = $customer->phone;
        if (!$phone) {
            return back()->with('danger', 'Nomor HP/WhatsApp pelanggan belum diisi.');
        }

        $message = $customer->buildDebtSummaryWaMessage();

        try {
            app(\App\Services\FarmReminders::class)->sendManual(app(\App\Tenancy\FarmContext::class)->get(), $phone, $message);
            if ($whatsApp->isLive()) {
                return back()->with('success', 'Rincian tagihan & utang berhasil dikirim lewat WhatsApp (Fonnte) ke ' . $phone . '.');
            }

            return back()->with('success', 'Rincian tagihan & utang tercatat dikirim ke ' . $phone . ' (mode simulasi/log; isi FONNTE_TOKEN di .env untuk kirim sungguhan).');
        } catch (\Throwable $e) {
            return back()->with('danger', 'Gagal mengirim WhatsApp (Fonnte): ' . $e->getMessage());
        }
    }
    public function returnDeposit(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'required|string|max:150',
        ], ['note.required' => 'Tulis alasannya, misalnya "dikembalikan tunai".']);

        try {
            $customer->useDeposit((float) $data['amount'], null, 'Dikembalikan / koreksi: ' . $data['note']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('danger', $e->getMessage());
        }

        \App\Audit\ActivityRecorder::custom($customer, 'updated', 'Titip uang ' . $customer->name . ' dikurangi ' . \App\Support\Format::rupiah($data['amount']) . ' (' . $data['note'] . ')');

        return back()->with('success', 'Titip uang ' . $customer->name . ' dikurangi ' . \App\Support\Format::rupiah($data['amount']) . '.');
    }
}
