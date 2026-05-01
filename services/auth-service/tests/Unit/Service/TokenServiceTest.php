<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Exception\InvalidRefreshTokenException;
use App\Repository\RefreshTokenRepository;
use App\Service\TokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private JWTTokenManagerInterface&MockObject $jwtManager;
    private TokenService $service;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->jwtManager             = $this->createMock(JWTTokenManagerInterface::class);

        $this->service = new TokenService(
            $this->refreshTokenRepository,
            $this->jwtManager,
        );
    }

    public function testCreateTokenPairReturnsExpectedShape(): void
    {
        $user = new User('alice@example.com', 'hash');
        $this->jwtManager->method('createFromPayload')->willReturn('access.jwt');
        $this->refreshTokenRepository->method('save');

        $result = $this->service->createTokenPair($user);

        $this->assertSame('access.jwt', $result['accessToken']);
        $this->assertNotEmpty($result['refreshToken']);
        $this->assertSame(TokenService::EXPIRES_IN, $result['expiresIn']);
    }

    public function testCreateTokenPairPersistsRefreshToken(): void
    {
        $user = new User('alice@example.com', 'hash');
        $this->jwtManager->method('createFromPayload')->willReturn('jwt');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(RefreshToken::class), true);

        $this->service->createTokenPair($user);
    }

    public function testRotateRefreshTokenReturnsNewPair(): void
    {
        $user  = new User('alice@example.com', 'hash');
        $token = $this->validRefreshToken($user);

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($token);
        $this->jwtManager->method('createFromPayload')->willReturn('new.jwt');
        $this->refreshTokenRepository->method('save');

        $result = $this->service->rotateRefreshToken('raw-token');

        $this->assertSame('new.jwt', $result['accessToken']);
        $this->assertNotEmpty($result['refreshToken']);
    }

    public function testRotateRefreshTokenRevokesOldToken(): void
    {
        $user  = new User('alice@example.com', 'hash');
        $token = $this->validRefreshToken($user);

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($token);
        $this->jwtManager->method('createFromPayload')->willReturn('jwt');
        $this->refreshTokenRepository->method('save');

        $this->service->rotateRefreshToken('raw-token');

        $this->assertNotNull($token->getRevokedAt());
    }

    public function testRotateRefreshTokenThrowsWhenNotFound(): void
    {
        $this->refreshTokenRepository->method('findByTokenHash')->willReturn(null);

        $this->expectException(InvalidRefreshTokenException::class);

        $this->service->rotateRefreshToken('bad-token');
    }

    public function testRotateRefreshTokenThrowsWhenRevoked(): void
    {
        $user  = new User('alice@example.com', 'hash');
        $token = $this->validRefreshToken($user);
        $token->revoke();

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($token);

        $this->expectException(InvalidRefreshTokenException::class);

        $this->service->rotateRefreshToken('raw-token');
    }

    private function validRefreshToken(User $user): RefreshToken
    {
        return new RefreshToken($user, hash('sha256', 'raw-token'), new \DateTimeImmutable('+30 days'));
    }
}