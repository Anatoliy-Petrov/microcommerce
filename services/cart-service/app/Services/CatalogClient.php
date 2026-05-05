<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final readonly class CatalogClient
{
    public function __construct(private string $baseUrl) {}

    /** @throws \RuntimeException */
    public function getProduct(string $productId): array
    {
        $response = Http::timeout(5)->get("{$this->baseUrl}/products/{$productId}");

        if (!$response->successful()) {
            throw new \RuntimeException("Product {$productId} not found in catalog");
        }

        $data = $response->json('data');

        return [
            'name'  => $data['name'],
            'price' => $data['price'],
        ];
    }
}
