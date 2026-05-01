<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Exception\DuplicateEmailException;
use App\Repository\UserRepository;
use Microcommerce\Common\Events\UserRegisteredEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EventPublisherService $eventPublisher,
    ) {}

    /** @throws DuplicateEmailException */
    public function register(RegisterRequest $request): User
    {
        if ($this->userRepository->emailExists($request->email)) {
            throw new DuplicateEmailException($request->email);
        }

        $user = new User($request->email, '');
        $hash = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPasswordHash($hash);

        $this->userRepository->save($user, true);

        $this->eventPublisher->publish(new UserRegisteredEvent(
            userId:    (string) $user->getId(),
            email:     $user->getEmail(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ));

        return $user;
    }
}