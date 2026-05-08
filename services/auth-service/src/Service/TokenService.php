<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Exception\InvalidRefreshTokenException;
use App\Repository\RefreshTokenRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class TokenService
{
    public const int EXPIRES_IN = 900;

    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
        private JWTTokenManagerInterface $jwtManager,
    ) {}

    /** @return array{accessToken: string, refreshToken: string, expiresIn: int} */
    public function createTokenPair(User $user): array
    {
        $jti         = bin2hex(random_bytes(16));
        $accessToken = $this->jwtManager->createFromPayload($user, [
            'jti'    => $jti,
            'userId' => (string) $user->getId(),
            'role'   => $user->getRole()->value,
        ]);

        $rawRefresh   = bin2hex(random_bytes(64));
        $hash         = hash('sha256', $rawRefresh);
        $expiresAt    = new \DateTimeImmutable('+30 days');
        $refreshToken = new RefreshToken($user, $hash, $expiresAt);
        $this->refreshTokenRepository->save($refreshToken, flush: true);

        return [
            'accessToken'  => $accessToken,
            'refreshToken' => $rawRefresh,
            'expiresIn'    => self::EXPIRES_IN,
        ];
    }

    /**
     * Revokes the current refresh token and issues a new token pair.
     *
     * @throws InvalidRefreshTokenException
     * @return array{accessToken: string, refreshToken: string, expiresIn: int}
     */
    public function rotateRefreshToken(string $rawToken): array
    {
        $hash         = hash('sha256', $rawToken);
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($hash);

        if ($refreshToken === null || !$refreshToken->isValid()) {
            throw new InvalidRefreshTokenException();
        }

        $user = $refreshToken->getUser();
        $refreshToken->revoke();

        return $this->createTokenPair($user);
    }
}