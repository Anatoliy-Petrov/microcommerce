<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class WebhookResult
{
    public function __construct(
        public string  $eventType,
        public string  $providerEventId,
        public ?string $orderId,
        public ?string $userId,
        public ?string $providerPaymentId,
        public bool    $completed,
        public ?string $failureReason = null,
    ) {}
}