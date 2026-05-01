<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class LogoutService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private TokenBlacklistService $blacklist,
        private RefreshTokenRepository $refreshTokenRepository,
        private EntityManagerInterface $em,
    ) {}

    public function logout(?string $rawAccessToken, ?string $rawRefreshToken): void
    {
        if ($rawAccessToken !== null) {
            try {
                $payload = $this->jwtManager->parse($rawAccessToken);
                $jti     = (string) ($payload['jti'] ?? '');
                $exp     = (int) ($payload['exp'] ?? 0);
                $ttl     = $exp - time();

                if ($jti !== '' && $ttl > 0) {
                    $this->blacklist->revoke($jti, $ttl);
                }
            } catch (\Throwable) {
                // Token already invalid — nothing to blacklist
            }
        }

        if ($rawRefreshToken !== null && $rawRefreshToken !== '') {
            $hash         = hash('sha256', $rawRefreshToken);
            $refreshToken = $this->refreshTokenRepository->findByTokenHash($hash);
            if ($refreshToken !== null && $refreshToken->isValid()) {
                $refreshToken->revoke();
                $this->em->flush();
            }
        }
    }
}