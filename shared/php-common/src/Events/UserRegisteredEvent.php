<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class UserRegisteredEvent extends DomainEvent
{
    public const NAME = 'user.registered';

    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $createdAt,
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
            'userId'    => $this->userId,
            'email'     => $this->email,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId:    $data['userId'],
            email:     $data['email'],
            createdAt: $data['createdAt'],
        );
    }
}