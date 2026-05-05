<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class StockUpdatedEvent extends DomainEvent
{
    public const NAME = 'catalog.stock_updated';

    public function __construct(
        public readonly string $productId,
        public readonly int    $previousQty,
        public readonly int    $newQty,
        public readonly string $reason,
    ) {
        parent::__construct();
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function toArray(): array
    {
        return [
            'productId'   => $this->productId,
            'previousQty' => $this->previousQty,
            'newQty'      => $this->newQty,
            'reason'      => $this->reason,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId:   $data['productId'],
            previousQty: $data['previousQty'],
            newQty:      $data['newQty'],
            reason:      $data['reason'],
        );
    }
}