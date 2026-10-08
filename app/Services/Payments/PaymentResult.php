<?php

namespace App\Services\Payments;

final class PaymentResult
{
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $method,
        public readonly ?string $gatewayRef,
        public readonly ?int $amount,
        public readonly array $raw,
    ) {}
}
