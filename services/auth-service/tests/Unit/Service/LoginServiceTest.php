<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Exception\InvalidCredentialsException;
use App\Repository\UserRepository;
use App\Service\LoginService;
use App\Service\TokenService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private TokenService&MockObject $tokenService;
    private LoginService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->tokenService   = $this->createMock(TokenService::class);

        $this->service = new LoginService(
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenService,
        );
    }

    public function testLoginReturnsTokenPairOnValidCredentials(): void
    {
        $user = new User('alice@example.com', 'hash');
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(true);
        $this->tokenService->method('createTokenPair')->willReturn([
            'accessToken'  => 'access.jwt',
            'refreshToken' => 'raw-refresh',
            'expiresIn'    => TokenService::EXPIRES_IN,
        ]);

        $result = $this->service->login('alice@example.com', 'password123');

        $this->assertSame('access.jwt', $result['accessToken']);
        $this->assertSame('raw-refresh', $result['refreshToken']);
        $this->assertSame(TokenService::EXPIRES_IN, $result['expiresIn']);
    }

    public function testLoginThrowsWhenUserNotFound(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->service->login('nobody@example.com', 'password123');
    }

    public function testLoginThrowsOnWrongPassword(): void
    {
        $user = new User('alice@example.com', 'hash');
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(false);

        $this->expectException(InvalidCredentialsException::class);

        $this->service->login('alice@example.com', 'wrongpassword');
    }

    public function testLoginDelegatesToTokenService(): void
    {
        $user = new User('alice@example.com', 'hash');
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(true);

        $this->tokenService
            ->expects($this->once())
            ->method('createTokenPair')
            ->with($user)
            ->willReturn(['accessToken' => 't', 'refreshToken' => 'r', 'expiresIn' => 900]);

        $this->service->login('alice@example.com', 'password123');
    }
}