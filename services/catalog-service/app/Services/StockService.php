<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Microcommerce\Common\Events\StockUpdatedEvent;

final readonly class StockService
{
    public function __construct(private EventPublisherService $eventPublisher) {}

    /** Decrease available stock (e.g. order placed). */
    public function reserve(string $productId, int $qty): Stock
    {
        return DB::transaction(function () use ($productId, $qty) {
            $stock = Stock::lockForUpdate()->findOrFail($productId);

            if (($stock->quantity - $stock->reserved_quantity) < $qty) {
                throw new \DomainException("Insufficient stock for product {$productId}");
            }

            $prev = $stock->quantity;
            $stock->increment('reserved_quantity', $qty);
            $stock->refresh();

            $this->eventPublisher->publish(new StockUpdatedEvent(
                productId:   $productId,
                previousQty: $prev,
                newQty:      $stock->quantity,
                reason:      'reserved',
            ));

            return $stock;
        });
    }

    /** Release reservation (e.g. order cancelled). */
    public function release(string $productId, int $qty): Stock
    {
        return DB::transaction(function () use ($productId, $qty) {
            $stock = Stock::lockForUpdate()->findOrFail($productId);

            $prev = $stock->quantity;
            $stock->decrement('reserved_quantity', min($qty, $stock->reserved_quantity));
            $stock->refresh();

            $this->eventPublisher->publish(new StockUpdatedEvent(
                productId:   $productId,
                previousQty: $prev,
                newQty:      $stock->quantity,
                reason:      'released',
            ));

            return $stock;
        });
    }

    /** Adjust physical quantity (e.g. warehouse restock). */
    public function adjust(string $productId, int $delta, string $reason): Stock
    {
        return DB::transaction(function () use ($productId, $delta, $reason) {
            $stock = Stock::lockForUpdate()->findOrFail($productId);

            $newQty = $stock->quantity + $delta;
            if ($newQty < 0) {
                throw new \DomainException("Stock quantity cannot go below zero for product {$productId}");
            }

            $prev = $stock->quantity;
            $stock->update(['quantity' => $newQty]);

            $this->eventPublisher->publish(new StockUpdatedEvent(
                productId:   $productId,
                previousQty: $prev,
                newQty:      $newQty,
                reason:      $reason,
            ));

            return $stock->refresh();
        });
    }

    public function createForProduct(string $productId, int $initialQty = 0): Stock
    {
        return Stock::create([
            'product_id'          => $productId,
            'quantity'            => $initialQty,
            'reserved_quantity'   => 0,
        ]);
    }
}