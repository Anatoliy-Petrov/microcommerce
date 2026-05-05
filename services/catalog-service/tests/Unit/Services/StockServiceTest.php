<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Services\EventPublisherService;
use App\Services\StockService;
use Mockery\MockInterface;
use Tests\TestCase;

final class StockServiceTest extends TestCase
{
    private StockService $service;
    private MockInterface $publisher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publisher = \Mockery::mock(EventPublisherService::class);
        $this->publisher->shouldReceive('publish')->byDefault();
        $this->service = new StockService($this->publisher);
    }

    public function testReserveDecreasesAvailableQty(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 10, 'reserved_quantity' => 0]);

        $stock = $this->service->reserve($product->id, 3);

        $this->assertSame(3, $stock->reserved_quantity);
        $this->assertSame(7, $stock->available_quantity);
    }

    public function testReserveThrowsWhenInsufficientStock(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 2, 'reserved_quantity' => 0]);

        $this->expectException(\DomainException::class);

        $this->service->reserve($product->id, 5);
    }

    public function testReleaseDecreasesReservedQty(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 10, 'reserved_quantity' => 4]);

        $stock = $this->service->release($product->id, 4);

        $this->assertSame(0, $stock->reserved_quantity);
    }

    public function testAdjustIncreasesPhysicalQty(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 5, 'reserved_quantity' => 0]);

        $stock = $this->service->adjust($product->id, 10, 'restock');

        $this->assertSame(15, $stock->quantity);
    }

    public function testAdjustThrowsWhenQtyWouldGoBelowZero(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 3, 'reserved_quantity' => 0]);

        $this->expectException(\DomainException::class);

        $this->service->adjust($product->id, -10, 'write-off');
    }

    public function testReservePublishesStockUpdatedEvent(): void
    {
        $product = Product::factory()->create();
        Stock::create(['product_id' => $product->id, 'quantity' => 10, 'reserved_quantity' => 0]);

        $this->publisher->shouldReceive('publish')
            ->once()
            ->with(\Mockery::type(\Microcommerce\Common\Events\StockUpdatedEvent::class));

        $this->service->reserve($product->id, 2);
    }
}