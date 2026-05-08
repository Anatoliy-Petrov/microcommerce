<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\Column(length: 36)]
    private string $userId;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToMany(targetEntity: OrderTransition::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $transitions;

    public function __construct(string $id, string $userId, string $subtotal, string $total)
    {
        $this->id          = $id;
        $this->userId      = $userId;
        $this->status      = OrderStatus::Pending->value;
        $this->subtotal    = $subtotal;
        $this->total       = $total;
        $this->createdAt   = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTimeImmutable();
        $this->items       = new ArrayCollection();
        $this->transitions = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getTotal(): string { return $this->total; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** Used by Symfony Workflow marking store */
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getStatusEnum(): OrderStatus { return OrderStatus::from($this->status); }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    /** @return Collection<int, OrderTransition> */
    public function getTransitions(): Collection { return $this->transitions; }

    public function addItem(OrderItem $item): void
    {
        $this->items->add($item);
    }

    public function addTransition(OrderTransition $transition): void
    {
        $this->transitions->add($transition);
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}