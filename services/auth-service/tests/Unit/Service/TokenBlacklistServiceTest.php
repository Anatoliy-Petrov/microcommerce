<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TokenBlacklistService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class TokenBlacklistServiceTest extends TestCase
{
    private Client&MockObject $redis;
    private TokenBlacklistService $service;

    protected function setUp(): void
    {
        $this->redis   = $this->createMock(Client::class);
        $this->service = new TokenBlacklistService($this->redis);
    }

    public function testRevokeStoresJtiWithTtl(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('setex')
            ->with('jti:abc123', 900, '1');

        $this->service->revoke('abc123', 900);
    }

    public function testRevokeIgnoresNonPositiveTtl(): void
    {
        $this->redis->expects($this->never())->method('setex');

        $this->service->revoke('abc123', 0);
        $this->service->revoke('abc123', -1);
    }

    public function testIsRevokedReturnsTrueWhenKeyExists(): void
    {
        $this->redis->method('exists')->with('jti:abc123')->willReturn(1);

        $this->assertTrue($this->service->isRevoked('abc123'));
    }

    public function testIsRevokedReturnsFalseWhenKeyAbsent(): void
    {
        $this->redis->method('exists')->with('jti:xyz')->willReturn(0);

        $this->assertFalse($this->service->isRevoked('xyz'));
    }
}