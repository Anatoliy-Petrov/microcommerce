<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class OrderShippedEvent extends DomainEvent
{
    public const NAME = 'order.shipped';

    public function __construct(
        public readonly string $orderId,
        public readonly string $trackingNumber,
        public readonly string $shippedAt,
    ) {
        parent::__construct();
    }

    public static function getName(): string { return self::NAME; }

    public function toArray(): array
    {
        return [
            'orderId'        => $this->orderId,
            'trackingNumber' => $this->trackingNumber,
            'shippedAt'      => $this->shippedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:        $data['orderId'],
            trackingNumber: $data['trackingNumber'],
            shippedAt:      $data['shippedAt'],
        );
    }
}