<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final readonly class JwtValidatorService
{
    public function __construct(private string $publicKeyPath) {}

    /**
     * Validates the token and returns the payload.
     *
     * @return array{userId: string, role: string}
     * @throws \RuntimeException on invalid or expired token
     */
    public function validate(string $token): array
    {
        $publicKey = file_get_contents($this->publicKeyPath);
        if ($publicKey === false) {
            throw new \RuntimeException('JWT public key not found');
        }

        try {
            $payload = JWT::decode($token, new Key($publicKey, 'RS256'));
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invalid or expired token: '.$e->getMessage(), 401, $e);
        }

        $data = (array) $payload;

        return [
            'userId' => (string) ($data['userId'] ?? $data['username'] ?? ''),
            'role'   => (string) ($data['role'] ?? 'user'),
        ];
    }
}
