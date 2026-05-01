<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefreshRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Refresh token is required')]
        public string $refreshToken,
    ) {}
}