<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class PaymentFailedEvent extends DomainEvent
{
    public const NAME = 'payment.failed';

    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
        public readonly string $provider,
        public readonly string $reason,
        public readonly string $failedAt,
    ) {
        parent::__construct();
    }

    public static function getName(): string { return self::NAME; }

    public function toArray(): array
    {
        return [
            'orderId'  => $this->orderId,
            'userId'   => $this->userId,
            'provider' => $this->provider,
            'reason'   => $this->reason,
            'failedAt' => $this->failedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:  $data['orderId'],
            userId:   $data['userId'],
            provider: $data['provider'],
            reason:   $data['reason'],
            failedAt: $data['failedAt'],
        );
    }
}