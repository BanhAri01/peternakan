<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use App\Services\Plans;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MidtransGateway implements PaymentGateway
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'midtrans';
    }

    public function createCharge(SubscriptionPayment $payment, array $customer, string $finishUrl): string
    {
        try {
            $response = $this->client($this->snapUrl())->post('/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $payment->reference, 'gross_amount' => (int) $payment->amount],
                'customer_details'    => [
                    'first_name' => Str::limit($customer['name'], 50, ''),
                    'email'      => $customer['email'],
                    'phone'      => $customer['phone'],
                ],
                'item_details'        => [[
                    'id'       => 'HEFAM-' . strtoupper($payment->plan) . '-' . $payment->months . 'BLN',
                    'name'     => Str::limit('HEFAM ' . Plans::label($payment->plan) . ' ' . $payment->months . ' bulan', 50, ''),
                    'price'    => (int) $payment->amount,
                    'quantity' => 1,
                ]],
                'enabled_payments'    => ['other_qris', 'gopay', 'shopeepay', 'bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel', 'other_va'],
                'expiry'              => ['unit' => 'hour', 'duration' => (int) config('services.payment.expiry_hours')],
                'callbacks'           => ['finish' => $finishUrl],
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak bisa terhubung ke Midtrans.', previous: $exception);
        }

        if ($response->failed() || !$response->json('redirect_url')) {
            throw new RuntimeException('Midtrans menolak transaksi: ' . implode(', ', (array) $response->json('error_messages', [$response->status()])));
        }

        return (string) $response->json('redirect_url');
    }

    public function fetchStatus(string $reference): ?PaymentResult
    {
        try {
            $response = $this->client($this->coreUrl())->get('/v2/' . rawurlencode($reference) . '/status');
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed() || (string) $response->json('status_code') === '404') {
            return null;
        }

        return $this->parseNotification((array) $response->json());
    }

    public function parseNotification(array $payload): ?PaymentResult
    {
        $orderId  = (string) ($payload['order_id'] ?? '');
        $expected = hash('sha512', $orderId . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . $this->config['server_key']);

        if ($orderId === '' || blank($this->config['server_key']) || !hash_equals($expected, (string) ($payload['signature_key'] ?? ''))) {
            return null;
        }

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $status = match ($transactionStatus) {
            'capture'                   => ($payload['fraud_status'] ?? 'accept') === 'accept' ? 'paid' : 'pending',
            'settlement'                => 'paid',
            'deny', 'cancel', 'failure' => 'failed',
            'expire'                    => 'expired',
            default                     => 'pending',
        };

        $method = (string) ($payload['payment_type'] ?? '');

        return new PaymentResult(
            reference: $orderId,
            status: $status,
            method: $method !== '' ? $method : null,
            gatewayRef: $payload['transaction_id'] ?? null,
            amount: isset($payload['gross_amount']) ? (int) round((float) $payload['gross_amount']) : null,
            raw: $payload,
        );
    }

    private function client(string $baseUrl): PendingRequest
    {
        if (blank($this->config['server_key'])) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        return Http::baseUrl($baseUrl)
            ->withBasicAuth((string) $this->config['server_key'], '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 300, fn (Throwable $exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()), throw: false);
    }

    private function snapUrl(): string
    {
        return $this->config['is_production'] ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }

    private function coreUrl(): string
    {
        return $this->config['is_production'] ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }
}
