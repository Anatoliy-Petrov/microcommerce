<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly string $occurredAt;

    public function __construct()
    {
        $this->eventId = \bin2hex(\random_bytes(16));
        $this->occurredAt = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }

    abstract public static function getName(): string;

    abstract public function toArray(): array;

    public function toEnvelope(): array
    {
        return [
            'eventId'    => $this->eventId,
            'eventName'  => static::getName(),
            'occurredAt' => $this->occurredAt,
            'payload'    => $this->toArray(),
        ];
    }
}