<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\CheckoutRequest;
use App\DTO\CheckoutResult;
use App\DTO\WebhookResult;

interface PaymentProviderInterface
{
    public function getName(): string;

    public function createCheckoutSession(CheckoutRequest $request): CheckoutResult;

    /** @param array<string, string> $headers */
    public function handleWebhook(string $rawBody, array $headers): WebhookResult;
}