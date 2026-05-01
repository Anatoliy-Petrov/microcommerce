<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidCredentialsException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class LoginService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService,
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @return array{accessToken: string, refreshToken: string, expiresIn: int}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenService->createTokenPair($user);
    }
}