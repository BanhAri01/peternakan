<?php

namespace App\Services\Payments;

use App\Audit\ActivityRecorder;
use App\Models\Farm;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Format;
use App\Tenancy\FarmScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubscriptionBilling
{
    public function __construct(private PaymentGateway $gateway) {}

    public static function plans(): array
    {
        return config('hefam.plans');
    }

    public function available(): bool
    {
        return match ($this->gateway->name()) {
            'midtrans' => filled(config('services.midtrans.server_key')),
            'fake'     => !app()->isProduction(),
            default    => false,
        };
    }

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }

    public function start(Farm $farm, User $user, int $months): SubscriptionPayment
    {
        $plans = self::plans();

        if (!isset($plans[$months])) {
            throw ValidationException::withMessages(['months' => 'Pilih paket langganan yang tersedia.']);
        }
        if (!$this->available()) {
            throw ValidationException::withMessages(['months' => 'Pembayaran online belum aktif. Silakan hubungi admin HEFAM.']);
        }
        if ($farm->status === 'suspended') {
            throw ValidationException::withMessages(['months' => 'Peternakan sedang dibekukan. Hubungi admin HEFAM.']);
        }
        if ($farm->hasUnlimitedAccess()) {
            throw ValidationException::withMessages(['months' => 'Langganan Anda aktif tanpa batas waktu, tidak perlu membayar.']);
        }

        $payment = SubscriptionPayment::create([
            'user_id'   => $user->id,
            'reference' => 'HEFAM-' . $farm->id . '-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4)),
            'gateway'   => $this->gateway->name(),
            'months'    => $months,
            'amount'    => $plans[$months],
            'status'    => 'pending',
        ]);

        try {
            $redirect = $this->gateway->createCharge($payment, [
                'name'  => $farm->owner_name ?: $user->name,
                'email' => $user->email,
                'phone' => $farm->phone,
            ], route('subscription.finish', ['ref' => $payment->reference]));
        } catch (RuntimeException $e) {
            $payment->update(['status' => 'failed']);
            Log::warning('Gagal membuat tagihan langganan ' . $payment->reference . ': ' . $e->getMessage());

            throw ValidationException::withMessages(['months' => 'Gagal membuat tagihan: ' . $e->getMessage()]);
        }

        $payment->update(['redirect_url' => $redirect]);

        return $payment;
    }

    public function apply(PaymentResult $result): ?SubscriptionPayment
    {
        return DB::transaction(function () use ($result) {
            $payment = SubscriptionPayment::withoutGlobalScope(FarmScope::class)
                ->where('reference', $result->reference)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning('Notifikasi pembayaran untuk referensi tidak dikenal: ' . $result->reference);

                return null;
            }

            $payment->last_payload = $result->raw;

            if ($payment->isPaid()) {
                $payment->save();

                return $payment;
            }

            if ($result->status === 'paid' && $result->amount !== null && $result->amount !== (int) $payment->amount) {
                Log::error('Nominal pembayaran tidak cocok untuk ' . $payment->reference . ': ' . $result->amount . ' vs ' . $payment->amount);
                $payment->save();

                return $payment;
            }

            if ($result->status !== 'paid') {
                if ($result->status !== 'pending') {
                    $payment->status = $result->status;
                }
                $payment->save();

                return $payment;
            }

            $farm = Farm::whereKey($payment->farm_id)->lockForUpdate()->firstOrFail();

            $payment->fill([
                'status'      => 'paid',
                'method'      => $result->method,
                'gateway_ref' => $result->gatewayRef,
                'paid_at'     => now(),
            ]);

            if ($farm->status === 'suspended') {
                $payment->save();
                Log::warning('Pembayaran ' . $payment->reference . ' diterima untuk peternakan yang dibekukan; tidak diperpanjang otomatis.');

                return $payment;
            }

            [$from, $until] = $farm->extendSubscription((int) $payment->months);
            $payment->fill(['period_from' => $from->toDateString(), 'period_until' => $until->toDateString()])->save();

            ActivityRecorder::custom($payment, 'paid', sprintf(
                'Langganan %d bulan dibayar %s%s, aktif sampai %s',
                $payment->months,
                Format::rupiah($payment->amount),
                $payment->method ? ' lewat ' . $payment->method : '',
                Format::date($until)
            ), [], $farm->id);

            return $payment;
        });
    }
}
