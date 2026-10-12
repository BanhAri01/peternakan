<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    /**
     * Tampilkan portal ringkasan tagihan & riwayat nota untuk pelanggan publik.
     */
    public function show(Request $request, int $customerId, string $token)
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($customerId);

        if (! hash_equals($customer->portalToken(), $token)) {
            abort(403, 'Tautan tagihan tidak valid atau sudah kedaluwarsa.');
        }

        if (! $customer->farm || ! $customer->farm->isAccessible()) {
            abort(403, 'Layanan portal peternakan ini sedang tidak aktif.');
        }

        // Aktifkan konteks peternakan pelanggan untuk mengambil data sesuai tenant
        app(FarmContext::class)->set($customer->farm);

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
        $farmName    = Setting::get('farm_name', $farm->name ?? 'HEFAM');
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
        $customer = Customer::withoutGlobalScopes()->findOrFail($customerId);

        if (! hash_equals($customer->portalToken(), $token)) {
            abort(403, 'Tautan nota tidak valid atau sudah kedaluwarsa.');
        }

        if (! $customer->farm || ! $customer->farm->isAccessible()) {
            abort(403, 'Layanan portal peternakan ini sedang tidak aktif.');
        }

        app(FarmContext::class)->set($customer->farm);

        $invoice = $customer->invoices()->findOrFail($invoiceId);
        $invoice->load(['customer', 'lines.grade', 'creator']);

        return app(ExportPdfController::class)->printReceipt($request, $invoice);
    }
}
