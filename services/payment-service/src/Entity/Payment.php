<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\Column(length: 36)]
    private string $orderId;

    #[ORM\Column(length: 36)]
    private string $userId;

    #[ORM\Column(length: 50)]
    private string $provider;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $providerPaymentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $providerSessionId = null;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $orderId,
        string $userId,
        string $provider,
        int    $amount,
        string $currency,
    ) {
        $this->id        = $id;
        $this->orderId   = $orderId;
        $this->userId    = $userId;
        $this->provider  = $provider;
        $this->amount    = $amount;
        $this->currency  = $currency;
        $this->status    = 'pending';
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getOrderId(): string { return $this->orderId; }
    public function getUserId(): string { return $this->userId; }
    public function getProvider(): string { return $this->provider; }
    public function getProviderPaymentId(): ?string { return $this->providerPaymentId; }
    public function getProviderSessionId(): ?string { return $this->providerSessionId; }
    public function getAmount(): int { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function markCompleted(string $providerPaymentId): void
    {
        $this->status            = 'completed';
        $this->providerPaymentId = $providerPaymentId;
    }

    public function markFailed(): void
    {
        $this->status = 'failed';
    }

    public function setProviderSessionId(string $sessionId): void
    {
        $this->providerSessionId = $sessionId;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}