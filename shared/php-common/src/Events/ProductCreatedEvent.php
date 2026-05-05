<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class ProductCreatedEvent extends DomainEvent
{
    public const NAME = 'catalog.product_created';

    public function __construct(
        public readonly string $productId,
        public readonly string $name,
        public readonly string $price,
        public readonly int    $categoryId,
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
            'productId'  => $this->productId,
            'name'       => $this->name,
            'price'      => $this->price,
            'categoryId' => $this->categoryId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId:  $data['productId'],
            name:       $data['name'],
            price:      $data['price'],
            categoryId: $data['categoryId'],
        );
    }
}