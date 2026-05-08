<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class OrderConfirmedEvent extends DomainEvent
{
    public const NAME = 'order.confirmed';

    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
        public readonly array  $items,
        public readonly string $total,
        public readonly string $confirmedAt,
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
            'total'       => $this->total,
            'confirmedAt' => $this->confirmedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:     $data['orderId'],
            userId:      $data['userId'],
            items:       $data['items'],
            total:       $data['total'],
            confirmedAt: $data['confirmedAt'],
        );
    }
}