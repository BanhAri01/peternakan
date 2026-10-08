<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\SubscriptionBilling;
use App\Support\Format;
use App\Tenancy\FarmContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionBilling $billing) {}

    public function show(FarmContext $context)
    {
        $farm = $context->get();
        abort_unless($farm, 404);

        return view('subscription.show', [
            'farm'     => $farm,
            'adminWa'  => config('hefam.admin_whatsapp'),
            'plans'    => SubscriptionBilling::plans(),
            'canPay'   => $this->billing->available() && $farm->status !== 'suspended' && !$farm->hasUnlimitedAccess(),
            'payments' => SubscriptionPayment::latest('id')->take(10)->get(),
            'pending'  => SubscriptionPayment::where('status', 'pending')
                ->whereNotNull('redirect_url')
                ->where('created_at', '>=', now()->subHours((int) config('services.payment.expiry_hours')))
                ->latest('id')
                ->first(),
        ]);
    }

    public function pay(Request $request, FarmContext $context)
    {
        $data = $request->validate(['months' => ['required', 'integer', Rule::in(array_keys(SubscriptionBilling::plans()))]]);

        $payment = $this->billing->start($context->get(), $request->user(), (int) $data['months']);

        return redirect()->away($payment->redirect_url);
    }

    public function finish(Request $request)
    {
        $payment = SubscriptionPayment::where('reference', (string) $request->query('ref', $request->query('order_id')))->firstOrFail();

        if ($payment->status === 'pending' && ($result = $this->billing->gateway()->fetchStatus($payment->reference))) {
            $payment = $this->billing->apply($result) ?? $payment;
        }

        $redirect = redirect()->route('subscription.show');

        return match ($payment->status) {
            'paid'    => $redirect->with('success', 'Terima kasih! Pembayaran ' . Format::rupiah($payment->amount) . ' diterima. Langganan aktif sampai ' . Format::date($payment->period_until) . '.'),
            'pending' => $redirect->with('warning', 'Pembayaran belum kami terima. Jika sudah membayar, tunggu beberapa menit lalu muat ulang halaman ini.'),
            default   => $redirect->with('error', 'Pembayaran ' . strtolower($payment->status_label) . '. Silakan coba lagi.'),
        };
    }

    public function webhook(Request $request)
    {
        $result = $this->billing->gateway()->parseNotification($request->all());

        if (!$result) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $payment = $this->billing->apply($result);

        return response()->json(['message' => 'ok', 'status' => $payment?->status]);
    }

    public function simulate(string $reference)
    {
        abort_if(app()->isProduction() || $this->billing->gateway()->name() !== 'fake', 404);

        $payment = SubscriptionPayment::where('reference', $reference)->firstOrFail();

        return view('subscription.simulate', compact('payment'));
    }

    public function simulateConfirm(Request $request, string $reference)
    {
        abort_if(app()->isProduction() || $this->billing->gateway()->name() !== 'fake', 404);

        $payment = SubscriptionPayment::where('reference', $reference)->firstOrFail();
        $status  = $request->validate(['status' => 'required|in:paid,failed'])['status'];

        $result = $this->billing->gateway()->parseNotification([
            'reference' => $payment->reference,
            'status'    => $status,
            'amount'    => $payment->amount,
            'signature' => FakeGateway::signature($payment->reference, $status),
        ]);

        $this->billing->apply($result);

        return redirect()->route('subscription.finish', ['ref' => $payment->reference]);
    }
}
