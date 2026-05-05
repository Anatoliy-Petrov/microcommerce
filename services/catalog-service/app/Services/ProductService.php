<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Microcommerce\Common\Events\ProductCreatedEvent;

final readonly class ProductService
{
    public function __construct(
        private StockService $stockService,
        private EventPublisherService $eventPublisher,
    ) {}

    public function list(int $perPage = 20, ?int $categoryId = null, bool $activeOnly = true): LengthAwarePaginator
    {
        $query = Product::with(['category', 'images', 'stock']);

        if ($activeOnly) {
            $query->where('is_active', true);
        }
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(string $id): Product
    {
        return Product::with(['category', 'images', 'stock'])->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $data['id'] = Str::uuid7()->toString();

        $product = Product::create($data);
        $this->stockService->createForProduct($product->id, $data['initial_stock'] ?? 0);
        $product->load(['category', 'images', 'stock']);

        $this->eventPublisher->publish(new ProductCreatedEvent(
            productId:  $product->id,
            name:       $product->name,
            price:      (string) $product->price,
            categoryId: (int) $product->category_id,
        ));

        return $product;
    }

    public function update(string $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product->fresh(['category', 'images', 'stock']);
    }

    public function delete(string $id): void
    {
        Product::findOrFail($id)->delete();
    }
}