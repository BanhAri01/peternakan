<?php

namespace App\Http\Controllers;

use App\Audit\ActivityRecorder;
use App\Tenancy\FarmRule;
use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\EggStock;
use App\Services\WhatsApp;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'customer_phone'         => [
                'nullable',
                Rule::requiredIf(function () use ($request) {
                    if (! in_array($request->input('payment_mode'), ['sebagian', 'tempo'], true)) {
                        return false;
                    }
                    if ($request->filled('customer_id')) {
                        $c = Customer::find($request->input('customer_id'));
                        if ($c && ! empty(trim((string) $c->phone))) {
                            return false;
                        }
                    }
                    return true;
                }),
                'string',
                'max:30',
            ],
            'sale_date'              => 'required|date|before_or_equal:today',
            'lines'                  => 'required|array|min:1|max:20',
            'lines.*.egg_grade_id'   => ['required', FarmRule::exists('egg_grades')],
            'lines.*.unit_type'      => 'required|in:kg,krat,butir',
            'lines.*.quantity_unit'  => 'required|numeric|min:0.01|max:1000000',
            'lines.*.price_per_unit' => 'required|numeric|min:1|max:100000000',
            'lines.*.weight_kg'      => 'nullable|numeric|min:0|max:1000000',
            'payment_mode'           => 'required|in:lunas,sebagian,tempo',
            'paid_amount'            => 'nullable|required_if:payment_mode,sebagian|numeric|min:0',
            'due_date'               => 'nullable|date',
            'notes'                  => 'nullable|string|max:500',
        ], [
            'customer_phone.required'      => 'Nomor HP/WhatsApp pembeli wajib diisi jika pembayaran belum lunas atau utang.',
            'customer_phone.required_if'   => 'Nomor HP/WhatsApp pembeli wajib diisi jika pembayaran belum lunas atau utang.',
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

        $invoice = ActivityRecorder::withoutRecording(fn () => DB::transaction(function () use ($data, $customer, $paid, $dueDate, $request) {
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
        }));

        ActivityRecorder::custom($invoice, 'created', 'Nota ' . $invoice->number . ' untuk ' . $customer->name . ' sebesar ' . Format::rupiah($total), [
            'customer_id'  => [null, $customer->name],
            'sale_date'    => [null, Format::date($data['sale_date'])],
            'total_amount' => [null, Format::rupiah($total)],
            'amount'       => [null, Format::rupiah($paid)],
        ]);

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
        $debt = (float) $invoice->debt;
        $customer = $invoice->customer;

        if ($request->boolean('use_deposit')) {
            if (! $customer || (float) $customer->deposit_balance <= 0) {
                return back()->with('danger', 'Pelanggan tidak memiliki saldo titip uang (deposit).');
            }

            $deduct = min($debt, (float) $customer->deposit_balance);
            DB::transaction(function () use ($invoice, $customer, $deduct) {
                $customer->useDeposit($deduct, $invoice, 'Potong saldo deposit untuk pelunasan nota ' . $invoice->number);
                $invoice->applyPayment($deduct);
            });

            $left = $invoice->fresh()->debt;
            $msg = 'Saldo deposit ' . Format::rupiah($deduct) . ' berhasil dipotong untuk nota ' . $invoice->number . '.';
            if ($left > 0) {
                $msg .= ' Sisa tagihan nota: ' . Format::rupiah($left) . '.';
            } else {
                $msg .= ' Nota sudah LUNAS.';
            }

            return back()->with('success', $msg);
        }

        $request->validate([
            'payment_add' => 'required|numeric|min:1',
        ]);

        $paymentAdd    = (float) $request->payment_add;
        $payForInvoice = min($debt, $paymentAdd);
        $overpayment   = max(0.0, $paymentAdd - $debt);
        $customer      = $invoice->customer;

        DB::transaction(function () use ($invoice, $customer, $payForInvoice, $overpayment) {
            if ($payForInvoice > 0) {
                $invoice->applyPayment($payForInvoice);
            }

            if ($overpayment > 0 && $customer) {
                $customer->addDeposit(
                    $overpayment,
                    $invoice,
                    'Kelebihan pembayaran nota ' . $invoice->number
                );
            }
        });

        $left = $invoice->fresh()->debt;

        if ($overpayment > 0) {
            $message = 'Pembayaran ' . Format::rupiah($paymentAdd) . ' dicatat. Nota ' . $invoice->number . ' LUNAS (terpotong ' . Format::rupiah($payForInvoice) . '). Kelebihan ' . Format::rupiah($overpayment) . ' otomatis dicatat ke Titip Uang / Saldo Deposit ' . ($customer->name ?? '') . '.';
        } elseif ($left > 0) {
            $message = 'Pembayaran ' . Format::rupiah($paymentAdd) . ' dicatat. Sisa tagihan nota ' . $invoice->number . ': ' . Format::rupiah($left) . '.';
        } else {
            $message = 'Nota ' . $invoice->number . ' (' . ($customer->name ?? '') . ') sudah LUNAS.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Invoice $invoice)
    {
        $total = $invoice->total;

        ActivityRecorder::withoutRecording(fn () => DB::transaction(function () use ($invoice) {
            $invoice->lines()->delete();
            $invoice->delete();
        }));

        ActivityRecorder::custom($invoice, 'deleted', 'Nota ' . $invoice->number . ' (' . ($invoice->customer->name ?? '-') . ', ' . Format::rupiah($total) . ') dipindah ke Sampah');

        return back()->with('success', 'Nota ' . $invoice->number . ' dipindah ke Sampah. Stok telur kembali seperti semula.');
    }

    private function findOrCreateCustomer(string $name, ?string $phone): Customer
    {
        $name     = trim(preg_replace('/\s+/', ' ', $name));
        $customer = Customer::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Customer::create(['name' => $name]);

        if ($phone && $customer->phone !== $phone) {
            $customer->update(['phone' => $phone]);
        }

        return $customer;
    }

    public function sendReceiptWa(Invoice $invoice, WhatsApp $whatsApp)
    {
        $phone = $invoice->customer?->phone;
        if (!$phone) {
            return back()->with('danger', 'Nomor HP/WhatsApp pembeli belum tercatat pada nota ini.');
        }

        $message = self::makeWaMessage($invoice);

        try {
            $whatsApp->send($phone, $message);
            if ($whatsApp->isLive()) {
                return back()->with('success', 'Nota ' . $invoice->number . ' berhasil dikirim lewat WhatsApp (Fonnte) ke ' . $phone . '.');
            }

            return back()->with('success', 'Nota ' . $invoice->number . ' tercatat dikirim ke ' . $phone . ' (mode simulasi/log; isi FONNTE_TOKEN di .env untuk kirim sungguhan).');
        } catch (\Throwable $e) {
            return back()->with('danger', 'Gagal mengirim WhatsApp (Fonnte): ' . $e->getMessage());
        }
    }

    public static function makeWaMessage(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'lines.grade']);

        if ($invoice->customer) {
            return $invoice->customer->buildDebtSummaryWaMessage($invoice);
        }

        $farmName     = Setting::get('farm_name', 'HEFAM');
        $customerName = 'Pelanggan';
        $date         = Format::date($invoice->sale_date);

        $lines = [];
        $lines[] = "*NOTA PENJUALAN TELUR*";
        $lines[] = "*{$farmName}*";
        $lines[] = "--------------------------------";
        $lines[] = "No. Nota : {$invoice->number}";
        $lines[] = "Tanggal  : {$date}";
        $lines[] = "Pembeli  : {$customerName}";
        $lines[] = "";
        $lines[] = "*Rincian Pembelian:*";

        foreach ($invoice->lines as $line) {
            $gradeName = $line->grade?->name ?? 'Telur';
            $qty       = Format::number($line->quantity_unit, 2);
            $unit      = $line->unit_label;
            $price     = Format::rupiah($line->price_per_unit);
            $subtotal  = Format::rupiah($line->total_amount);
            $lines[]   = "• {$gradeName}: {$qty} {$unit} × {$price} = {$subtotal}";
        }

        $lines[] = "";
        $lines[] = "Total Tagihan : " . Format::rupiah($invoice->total);
        if ($invoice->paid > 0) {
            $lines[] = "Sudah Dibayar : " . Format::rupiah($invoice->paid);
        }
        if ($invoice->debt > 0) {
            $lines[] = "*Sisa Utang*   : *" . Format::rupiah($invoice->debt) . "*";
            if ($invoice->due_date) {
                $lines[] = "Jatuh Tempo  : " . Format::date($invoice->due_date);
            }
        }
        $lines[] = "Status       : " . $invoice->status_label;

        if ($invoice->notes) {
            $lines[] = "Catatan      : {$invoice->notes}";
        }

        $lines[] = "--------------------------------";
        $lines[] = "Terima kasih telah berbelanja di {$farmName}.";

        return implode("\n", $lines);
    }
}
