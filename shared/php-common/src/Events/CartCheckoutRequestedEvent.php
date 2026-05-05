<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class CartCheckoutRequestedEvent extends DomainEvent
{
    public const NAME = 'cart.checkout_requested';

    public function __construct(
        public readonly string $userId,
        public readonly array  $items,
        public readonly string $total,
        public readonly string $requestedAt,
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
            'userId'      => $this->userId,
            'items'       => $this->items,
            'total'       => $this->total,
            'requestedAt' => $this->requestedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId:       $data['userId'],
            items:        $data['items'],
            total:        $data['total'],
            requestedAt:  $data['requestedAt'],
        );
    }
}
