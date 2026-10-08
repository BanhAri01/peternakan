<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\URL;

class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCharge(SubscriptionPayment $payment, array $customer, string $finishUrl): string
    {
        return URL::temporarySignedRoute('subscription.simulate', now()->addHours(2), ['reference' => $payment->reference]);
    }

    public function fetchStatus(string $reference): ?PaymentResult
    {
        return null;
    }

    public function parseNotification(array $payload): ?PaymentResult
    {
        $reference = (string) ($payload['reference'] ?? '');
        $status    = (string) ($payload['status'] ?? '');

        if ($reference === '' || !hash_equals(self::signature($reference, $status), (string) ($payload['signature'] ?? ''))) {
            return null;
        }

        return new PaymentResult($reference, $status, 'simulasi', 'SIM-' . $reference, isset($payload['amount']) ? (int) $payload['amount'] : null, $payload);
    }

    public static function signature(string $reference, string $status): string
    {
        return hash_hmac('sha256', $reference . '|' . $status, (string) config('app.key'));
    }
}
