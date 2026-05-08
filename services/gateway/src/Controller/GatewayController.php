<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\JwtValidatorService;
use App\Service\ProxyService;
use App\Service\RouteResolverService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class GatewayController
{
    public function __construct(
        private RouteResolverService $routeResolver,
        private JwtValidatorService  $jwtValidator,
        private ProxyService         $proxy,
    ) {}

    public function proxy(Request $request, string $path): Response
    {
        $fullPath = '/'.$path;

        try {
            $upstream = $this->routeResolver->resolve($fullPath);
        } catch (\RuntimeException $e) {
            return $this->error('Not found', Response::HTTP_NOT_FOUND);
        }

        $extraHeaders = [];

        if (!$this->routeResolver->isPublic($request->getMethod(), $fullPath)) {
            $authHeader = $request->headers->get('Authorization', '');
            $token      = str_starts_with($authHeader, 'Bearer ')
                ? substr($authHeader, 7)
                : null;

            if ($token === null) {
                return $this->error('Missing Authorization header', Response::HTTP_UNAUTHORIZED);
            }

            try {
                $claims = $this->jwtValidator->validate($token);
            } catch (\RuntimeException $e) {
                return $this->error($e->getMessage(), Response::HTTP_UNAUTHORIZED);
            }

            $extraHeaders['X-User-Id']   = $claims['userId'];
            $extraHeaders['X-User-Role'] = $claims['role'];
        }

        return $this->proxy->forward($request, $upstream, $extraHeaders);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(
            ['data' => null, 'meta' => new \stdClass(), 'errors' => [['message' => $message]]],
            $status,
        );
    }
}
