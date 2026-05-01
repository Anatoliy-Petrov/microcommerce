<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RefreshRequest;
use App\DTO\RegisterRequest;
use App\Exception\DuplicateEmailException;
use App\Exception\InvalidCredentialsException;
use App\Exception\InvalidRefreshTokenException;
use App\Service\LoginService;
use App\Service\LogoutService;
use App\Service\RegisterService;
use App\Service\TokenBlacklistService;
use App\Service\TokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractApiController
{
    public function __construct(
        private readonly RegisterService $registerService,
        private readonly LoginService $loginService,
        private readonly TokenService $tokenService,
        private readonly LogoutService $logoutService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly TokenBlacklistService $blacklist,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'limiter.login_limiter')]
        private readonly RateLimiterFactory $loginLimiterFactory,
        ValidatorInterface $validator,
    ) {
        parent::__construct($validator);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error([['message' => 'Invalid JSON body']], Response::HTTP_BAD_REQUEST);
        }

        $dto = new RegisterRequest(
            email:    trim((string) ($data['email'] ?? '')),
            password: (string) ($data['password'] ?? ''),
        );

        if ($response = $this->validateRequest($dto)) {
            return $response;
        }

        try {
            $user = $this->registerService->register($dto);
        } catch (DuplicateEmailException) {
            return $this->error([['field' => 'email', 'message' => 'Email already registered']], Response::HTTP_CONFLICT);
        }

        return $this->success([
            'userId' => (string) $user->getId(),
            'email'  => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $limiter = $this->loginLimiterFactory->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->error([['message' => 'Too many login attempts. Try again later.']], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error([['message' => 'Invalid JSON body']], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->loginService->login(
                email:    trim((string) ($data['email'] ?? '')),
                password: (string) ($data['password'] ?? ''),
            );
        } catch (InvalidCredentialsException) {
            return $this->error([['message' => 'Invalid credentials']], Response::HTTP_UNAUTHORIZED);
        }

        return $this->success($tokens);
    }

    public function refresh(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        if ($data === null) {
            return $this->error([['message' => 'Invalid JSON body']], Response::HTTP_BAD_REQUEST);
        }

        $dto = new RefreshRequest(refreshToken: (string) ($data['refreshToken'] ?? ''));

        if ($response = $this->validateRequest($dto)) {
            return $response;
        }

        try {
            $tokens = $this->tokenService->rotateRefreshToken($dto->refreshToken);
        } catch (InvalidRefreshTokenException) {
            return $this->error([['message' => 'Invalid or expired refresh token']], Response::HTTP_UNAUTHORIZED);
        }

        return $this->success($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);

        $this->logoutService->logout(
            rawAccessToken:  $this->extractBearerToken($request),
            rawRefreshToken: $data !== null ? (string) ($data['refreshToken'] ?? '') : null,
        );

        return $this->success(null, Response::HTTP_NO_CONTENT);
    }

    public function validate(Request $request): JsonResponse
    {
        $rawToken = $this->extractBearerToken($request);
        if ($rawToken !== null) {
            try {
                $payload = $this->jwtManager->parse($rawToken);
                $jti     = (string) ($payload['jti'] ?? '');
                if ($jti !== '' && $this->blacklist->isRevoked($jti)) {
                    return $this->error([['message' => 'Token has been revoked']], Response::HTTP_UNAUTHORIZED);
                }
                $exp = (int) ($payload['exp'] ?? 0);
            } catch (\Throwable) {
                return $this->error([['message' => 'Invalid token']], Response::HTTP_UNAUTHORIZED);
            }
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->success([
            'userId'    => (string) $user->getId(),
            'email'     => $user->getEmail(),
            'expiresAt' => isset($exp)
                ? (new \DateTimeImmutable('@' . $exp))->format(\DateTimeInterface::ATOM)
                : null,
        ]);
    }

    // --- Helpers ---

    private function parseJson(Request $request): ?array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }
        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->headers->get('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}