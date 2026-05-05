<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Microcommerce\Common\Events\CartCheckoutRequestedEvent;

final readonly class CartService
{
    private const TTL    = 604800; // 7 days
    private const PREFIX = 'cart:';

    public function __construct(
        private CatalogClient $catalogClient,
        private EventPublisherService $eventPublisher,
    ) {}

    public function getCart(string $userId): array
    {
        $raw   = Redis::connection('cart')->hgetall(self::PREFIX.$userId);
        $items = array_values(array_map(fn ($v) => json_decode($v, true), $raw));

        return [
            'userId' => $userId,
            'items'  => $items,
            'total'  => $this->computeTotal($items),
        ];
    }

    public function addItem(string $userId, string $productId, int $quantity): void
    {
        $redis = Redis::connection('cart');
        $key   = self::PREFIX.$userId;

        $existing = $redis->hget($key, $productId);

        if ($existing !== null && $existing !== false) {
            $item             = json_decode($existing, true);
            $item['quantity'] += $quantity;
        } else {
            $product = $this->catalogClient->getProduct($productId);
            $item    = [
                'productId' => $productId,
                'name'      => $product['name'],
                'price'     => $product['price'],
                'quantity'  => $quantity,
                'addedAt'   => now()->toIso8601String(),
            ];
        }

        $redis->hset($key, $productId, json_encode($item));
        $redis->expire($key, self::TTL);
    }

    public function updateItem(string $userId, string $productId, int $quantity): void
    {
        $redis    = Redis::connection('cart');
        $key      = self::PREFIX.$userId;
        $existing = $redis->hget($key, $productId);

        if ($existing === null || $existing === false) {
            throw new \DomainException("Item {$productId} not found in cart");
        }

        $item             = json_decode($existing, true);
        $item['quantity'] = $quantity;

        $redis->hset($key, $productId, json_encode($item));
        $redis->expire($key, self::TTL);
    }

    public function removeItem(string $userId, string $productId): void
    {
        $redis = Redis::connection('cart');
        $key   = self::PREFIX.$userId;

        $redis->hdel($key, $productId);

        if ($redis->hlen($key) > 0) {
            $redis->expire($key, self::TTL);
        }
    }

    public function clearCart(string $userId): void
    {
        Redis::connection('cart')->del(self::PREFIX.$userId);
    }

    public function checkout(string $userId): array
    {
        $cart = $this->getCart($userId);

        if (empty($cart['items'])) {
            throw new \DomainException('Cart is empty');
        }

        $this->eventPublisher->publish(new CartCheckoutRequestedEvent(
            userId:      $userId,
            items:       $cart['items'],
            total:       $cart['total'],
            requestedAt: now()->toIso8601String(),
        ));

        $this->clearCart($userId);

        return $cart;
    }

    private function computeTotal(array $items): string
    {
        $total = array_reduce(
            $items,
            fn (float $carry, array $item) => $carry + ((float) $item['price'] * $item['quantity']),
            0.0,
        );

        return number_format($total, 2, '.', '');
    }
}
