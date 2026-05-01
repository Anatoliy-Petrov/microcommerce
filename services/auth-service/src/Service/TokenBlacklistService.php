<?php

declare(strict_types=1);

namespace App\Service;

use Predis\Client;

class TokenBlacklistService
{
    private const KEY_PREFIX = 'jti:';

    public function __construct(private readonly Client $redis) {}

    public function revoke(string $jti, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }
        $this->redis->setex(self::KEY_PREFIX . $jti, $ttlSeconds, '1');
    }

    public function isRevoked(string $jti): bool
    {
        return (bool) $this->redis->exists(self::KEY_PREFIX . $jti);
    }
}