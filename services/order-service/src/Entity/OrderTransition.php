<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderTransitionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderTransitionRepository::class)]
#[ORM\Table(name: 'order_transitions')]
class OrderTransition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'transitions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(length: 20)]
    private string $fromState;

    #[ORM\Column(length: 20)]
    private string $toState;

    #[ORM\Column(length: 100)]
    private string $triggeredBy;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Order $order, string $fromState, string $toState, string $triggeredBy)
    {
        $this->order       = $order;
        $this->fromState   = $fromState;
        $this->toState     = $toState;
        $this->triggeredBy = $triggeredBy;
        $this->createdAt   = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOrder(): Order { return $this->order; }
    public function getFromState(): string { return $this->fromState; }
    public function getToState(): string { return $this->toState; }
    public function getTriggeredBy(): string { return $this->triggeredBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}