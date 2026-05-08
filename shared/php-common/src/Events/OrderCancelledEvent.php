<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class OrderCancelledEvent extends DomainEvent
{
    public const NAME = 'order.cancelled';

    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
        public readonly array  $items,
        public readonly string $reason,
        public readonly string $cancelledAt,
    ) {
        parent::__construct();
    }

    public static function getName(): string { return self::NAME; }

    public function toArray(): array
    {
        return [
            'orderId'     => $this->orderId,
            'userId'      => $this->userId,
            'items'       => $this->items,
            'reason'      => $this->reason,
            'cancelledAt' => $this->cancelledAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:     $data['orderId'],
            userId:      $data['userId'],
            items:       $data['items'],
            reason:      $data['reason'],
            cancelledAt: $data['cancelledAt'],
        );
    }
}