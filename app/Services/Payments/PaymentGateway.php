<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;

interface PaymentGateway
{
    public function name(): string;

    public function createCharge(SubscriptionPayment $payment, array $customer, string $finishUrl): string;

    public function fetchStatus(string $reference): ?PaymentResult;

    public function parseNotification(array $payload): ?PaymentResult;
}
