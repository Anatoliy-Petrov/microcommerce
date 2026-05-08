<?php

declare(strict_types=1);

namespace App\Service;

final readonly class RouteResolverService
{
    /** @param array<string, string> $upstreamMap prefix → base URL */
    public function __construct(private array $upstreamMap) {}

    public function resolve(string $path): string
    {
        $segment = ltrim(explode('/', $path)[1] ?? '', '/');

        if (!isset($this->upstreamMap[$segment])) {
            throw new \RuntimeException("No upstream configured for path: {$path}", 404);
        }

        return $this->upstreamMap[$segment];
    }

    public function isPublic(string $method, string $path): bool
    {
        $method = strtoupper($method);

        return match(true) {
            // Auth endpoints that don't need a token
            $path === '/auth/register'  && $method === 'POST' => true,
            $path === '/auth/login'     && $method === 'POST' => true,
            $path === '/auth/refresh'   && $method === 'POST' => true,

            // Catalog reads are public
            str_starts_with($path, '/products')   && $method === 'GET' => true,
            str_starts_with($path, '/categories') && $method === 'GET' => true,

            default => false,
        };
    }
}
