<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Exception\DuplicateEmailException;
use App\Repository\UserRepository;
use App\Service\EventPublisherService;
use App\Service\RegisterService;
use Microcommerce\Common\Events\UserRegisteredEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private EventPublisherService&MockObject $eventPublisher;
    private RegisterService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherService::class);

        $this->service = new RegisterService(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventPublisher,
        );
    }

    public function testRegisterCreatesAndSavesUser(): void
    {
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_secret');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), true);

        $user = $this->service->register(new RegisterRequest('alice@example.com', 'password123'));

        $this->assertSame('alice@example.com', $user->getEmail());
        $this->assertSame('hashed_secret', $user->getPasswordHash());
    }

    public function testRegisterPublishesUserRegisteredEvent(): void
    {
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_secret');
        $this->userRepository->method('save');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UserRegisteredEvent::class));

        $this->service->register(new RegisterRequest('bob@example.com', 'password123'));
    }

    public function testRegisterThrowsOnDuplicateEmail(): void
    {
        $this->userRepository->method('emailExists')->willReturn(true);

        $this->expectException(DuplicateEmailException::class);

        $this->service->register(new RegisterRequest('dupe@example.com', 'password123'));
    }

    public function testRegisterDoesNotSaveOnDuplicateEmail(): void
    {
        $this->userRepository->method('emailExists')->willReturn(true);
        $this->userRepository->expects($this->never())->method('save');

        try {
            $this->service->register(new RegisterRequest('dupe@example.com', 'password123'));
        } catch (DuplicateEmailException) {
        }
    }
}