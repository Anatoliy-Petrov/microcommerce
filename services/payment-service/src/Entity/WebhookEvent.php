<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WebhookEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebhookEventRepository::class)]
#[ORM\Table(name: 'webhook_events')]
#[ORM\UniqueConstraint(name: 'UNIQ_webhook_provider_event', columns: ['provider', 'provider_event_id'])]
class WebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $provider;

    #[ORM\Column(length: 255)]
    private string $providerEventId;

    #[ORM\Column(length: 100)]
    private string $type;

    #[ORM\Column]
    private \DateTimeImmutable $processedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    public function __construct(string $provider, string $providerEventId, string $type)
    {
        $this->provider        = $provider;
        $this->providerEventId = $providerEventId;
        $this->type            = $type;
        $this->processedAt     = new \DateTimeImmutable();
    }

    public function setError(string $error): void { $this->error = $error; }
    public function getId(): ?int { return $this->id; }
    public function getProvider(): string { return $this->provider; }
    public function getProviderEventId(): string { return $this->providerEventId; }
    public function getType(): string { return $this->type; }
}