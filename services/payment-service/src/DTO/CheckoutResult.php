<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CheckoutResult
{
    public function __construct(
        public string $sessionUrl,
        public string $providerSessionId,
    ) {}
}