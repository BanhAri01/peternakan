<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\FarmReminders;
use App\Support\Format;
use App\Tenancy\FarmContext;
use App\Tenancy\FarmScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class CustomerPortalController extends Controller
{
    private const RESEND_COOLDOWN_SECONDS = 3 * 3600;

    /**
     * Tampilkan portal ringkasan tagihan & riwayat nota untuk pelanggan publik.
     */
    public function show(Request $request, int $customerId, string $token)
    {
        $customer = $this->resolve($customerId, $token);

        if ($customer->portalTokenStatus($token) === 'expired') {
            return $this->expired($customer, $token);
        }

        $unpaidInvoices = $customer->invoices()
            ->withTotals()
            ->with(['lines.grade'])
            ->whereHas('lines', fn ($q) => $q->where('debt_amount', '>', 0))
            ->orderBy('sale_date', 'asc')
            ->get();

        $paidInvoices = $customer->invoices()
            ->withTotals()
            ->with(['lines.grade'])
            ->whereDoesntHave('lines', fn ($q) => $q->where('debt_amount', '>', 0))
            ->orderBy('sale_date', 'desc')
            ->limit(10)
            ->get();

        $today = Carbon::today();
        $overdueInvoices  = collect();
        $upcomingInvoices = collect();
        $overdueTotal     = 0.0;
        $upcomingTotal    = 0.0;

        foreach ($unpaidInvoices as $inv) {
            $isOverdue = $inv->due_date && $inv->due_date->lte($today);
            if ($isOverdue) {
                $overdueInvoices->push($inv);
                $overdueTotal += (float) $inv->debt;
            } else {
                $upcomingInvoices->push($inv);
                $upcomingTotal += (float) $inv->debt;
            }
        }

        $grandTotal     = $overdueTotal + $upcomingTotal;
        $depositBalance = (float) $customer->deposit_balance;
        $netDebt        = max(0.0, $grandTotal - $depositBalance);
        $deposits       = $customer->deposits()->with('invoice')->latest()->limit(10)->get();

        $farm        = $customer->farm;
        $owner       = \App\Models\User::where('farm_id', $farm->id)->where('role', 'owner')->first();
        $farmName    = Setting::get('farm_name', $farm->name ?? 'Kandala');
        $farmPhone   = Setting::get('farm_phone') ?: ($farm?->phone ?: ($owner?->phone ?: ''));
        $farmAddress = Setting::get('farm_address') ?: ($farm?->city ?: '');
        $farmOwner   = Setting::get('farm_owner') ?: ($farm?->owner_name ?: ($owner?->name ?: 'Pemilik Peternakan'));
        $farmTagline = Setting::get('farm_tagline');

        $highlightInvoiceId = $request->query('nota');
        $highlightInvoice   = $highlightInvoiceId
            ? $customer->invoices()->withTotals()->with(['lines.grade'])->find($highlightInvoiceId)
            : null;

        return view('portal.customer', compact(
            'customer',
            'token',
            'unpaidInvoices',
            'paidInvoices',
            'overdueInvoices',
            'upcomingInvoices',
            'overdueTotal',
            'upcomingTotal',
            'grandTotal',
            'depositBalance',
            'netDebt',
            'deposits',
            'farmName',
            'farmPhone',
            'farmAddress',
            'farmOwner',
            'farmTagline',
            'highlightInvoice'
        ));
    }

    /**
     * Cetak / unduh nota PDF dari portal pelanggan.
     */
    public function printReceipt(Request $request, int $customerId, string $token, int $invoiceId)
    {
        $customer = $this->resolve($customerId, $token);

        if ($customer->portalTokenStatus($token) === 'expired') {
            return redirect()->route('portal.customer', [$customer->id, $token]);
        }

        $invoice = $customer->invoices()->findOrFail($invoiceId);
        $invoice->load(['customer', 'lines.grade', 'creator']);

        return app(ExportPdfController::class)->printReceipt($request, $invoice);
    }

    // Link asli yang sudah lewat masa berlaku: kirim link baru hanya ke nomor WA pelanggan yang tercatat
    public function resendLink(int $customerId, string $token, FarmReminders $reminders)
    {
        $customer = $this->resolve($customerId, $token);
        $back     = redirect()->route('portal.customer', [$customer->id, $token]);

        if ($customer->portalTokenStatus($token) === 'valid') {
            return $back;
        }

        if (! $customer->wa_number) {
            return $back->with('error', 'Nomor WhatsApp Anda belum tercatat di peternakan. Silakan hubungi peternakan untuk minta link baru.');
        }

        $key = 'portal-link-baru:' . $customer->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return $back->with('success', 'Link baru sudah dikirim ke WhatsApp Anda. Silakan cek pesan WhatsApp.');
        }

        $farmName = Setting::get('farm_name', $customer->farm->name ?? 'Kandala');
        $message  = "Halo {$customer->name}, ini link tagihan & nota terbaru dari {$farmName}:\n" . $customer->portalUrl()
            . "\n\nLink berlaku sampai " . Format::date(now()->addDays(Customer::PORTAL_DAYS)) . '.';

        try {
            $reminders->sendManual($customer->farm, $customer->phone, $message);
        } catch (Throwable $e) {
            Log::warning('Gagal kirim link portal baru ke ' . $customer->phone . ': ' . $e->getMessage());

            return $back->with('error', 'Link baru belum bisa dikirim otomatis. Silakan hubungi peternakan lewat tombol WhatsApp di bawah.');
        }

        RateLimiter::hit($key, self::RESEND_COOLDOWN_SECONDS);

        return $back->with('success', 'Link baru sudah dikirim ke WhatsApp Anda. Silakan cek pesan WhatsApp.');
    }

    private function resolve(int $customerId, string $token): Customer
    {
        $customer = Customer::withoutGlobalScope(FarmScope::class)->findOrFail($customerId);

        if ($customer->portalTokenStatus($token) === 'invalid') {
            abort(403, 'Tautan tagihan tidak valid.');
        }

        if (! $customer->farm || ! $customer->farm->isAccessible()) {
            abort(403, 'Layanan portal peternakan ini sedang tidak aktif.');
        }

        // Aktifkan konteks peternakan pelanggan untuk mengambil data sesuai tenant
        app(FarmContext::class)->set($customer->farm);

        return $customer;
    }

    private function expired(Customer $customer, string $token)
    {
        $farm  = $customer->farm;
        $owner = \App\Models\User::where('farm_id', $farm->id)->where('role', 'owner')->first();

        return response()->view('portal.expired', [
            'customer'  => $customer,
            'token'     => $token,
            'farmName'  => Setting::get('farm_name', $farm->name ?? 'Kandala'),
            'farmPhone' => Setting::get('farm_phone') ?: ($farm->phone ?: ($owner?->phone ?: '')),
            'canResend' => (bool) $customer->wa_number,
        ], 410);
    }
}
