<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Service\LogoutService;
use App\Service\TokenBlacklistService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LogoutServiceTest extends TestCase
{
    private JWTTokenManagerInterface&MockObject $jwtManager;
    private TokenBlacklistService&MockObject $blacklist;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private EntityManagerInterface&MockObject $em;
    private LogoutService $service;

    protected function setUp(): void
    {
        $this->jwtManager             = $this->createMock(JWTTokenManagerInterface::class);
        $this->blacklist              = $this->createMock(TokenBlacklistService::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->em                     = $this->createMock(EntityManagerInterface::class);

        $this->service = new LogoutService(
            $this->jwtManager,
            $this->blacklist,
            $this->refreshTokenRepository,
            $this->em,
        );
    }

    public function testLogoutBlacklistsAccessTokenJti(): void
    {
        $exp = time() + 900;
        $this->jwtManager->method('parse')->willReturn(['jti' => 'abc123', 'exp' => $exp]);

        $this->blacklist
            ->expects($this->once())
            ->method('revoke')
            ->with('abc123', $this->greaterThan(0));

        $this->service->logout('raw.access.token', null);
    }

    public function testLogoutSkipsBlacklistWhenAccessTokenNull(): void
    {
        $this->blacklist->expects($this->never())->method('revoke');

        $this->service->logout(null, null);
    }

    public function testLogoutSkipsBlacklistWhenTokenExpired(): void
    {
        $this->jwtManager->method('parse')->willReturn(['jti' => 'abc', 'exp' => time() - 10]);

        $this->blacklist->expects($this->never())->method('revoke');

        $this->service->logout('raw.access.token', null);
    }

    public function testLogoutRevokesRefreshToken(): void
    {
        $user  = new User('alice@example.com', 'hash');
        $token = new RefreshToken($user, hash('sha256', 'raw-refresh'), new \DateTimeImmutable('+30 days'));

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($token);
        $this->em->expects($this->once())->method('flush');

        $this->service->logout(null, 'raw-refresh');

        $this->assertNotNull($token->getRevokedAt());
    }

    public function testLogoutSkipsRefreshRevocationWhenTokenInvalid(): void
    {
        $user  = new User('alice@example.com', 'hash');
        $token = new RefreshToken($user, hash('sha256', 'raw-refresh'), new \DateTimeImmutable('+30 days'));
        $token->revoke();

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($token);
        $this->em->expects($this->never())->method('flush');

        $this->service->logout(null, 'raw-refresh');
    }

    public function testLogoutHandlesInvalidAccessTokenGracefully(): void
    {
        $this->jwtManager->method('parse')->willThrowException(new \RuntimeException('bad token'));

        $this->blacklist->expects($this->never())->method('revoke');

        $this->service->logout('invalid.token', null);
    }
}