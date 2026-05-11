<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CheckoutRequest
{
    public function __construct(
        public string $orderId,
        public string $userId,
        public int    $amount,
        public string $currency,
        public string $successUrl,
        public string $cancelUrl,
    ) {}
}