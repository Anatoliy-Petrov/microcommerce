<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(length: 36)]
    private string $productId;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $unitPrice;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $lineTotal;

    public function __construct(
        string $id,
        Order  $order,
        string $productId,
        string $productName,
        string $unitPrice,
        int    $quantity,
    ) {
        $this->id          = $id;
        $this->order       = $order;
        $this->productId   = $productId;
        $this->productName = $productName;
        $this->unitPrice   = $unitPrice;
        $this->quantity    = $quantity;
        $this->lineTotal   = number_format((float) $unitPrice * $quantity, 2, '.', '');
    }

    public function getId(): string { return $this->id; }
    public function getOrder(): Order { return $this->order; }
    public function getProductId(): string { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function getQuantity(): int { return $this->quantity; }
    public function getLineTotal(): string { return $this->lineTotal; }

    public function toArray(): array
    {
        return [
            'productId'   => $this->productId,
            'productName' => $this->productName,
            'unitPrice'   => $this->unitPrice,
            'quantity'    => $this->quantity,
            'lineTotal'   => $this->lineTotal,
        ];
    }
}