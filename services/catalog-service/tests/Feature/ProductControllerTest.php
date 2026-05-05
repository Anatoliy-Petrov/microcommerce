<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Services\EventPublisherService;
use Mockery\MockInterface;
use Tests\TestCase;

final class ProductControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(EventPublisherService::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')->byDefault();
        });
    }

    public function testListReturnsActiveProducts(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta' => ['total', 'perPage', 'currentPage']]);
    }

    public function testShowReturnsSingleProduct(): void
    {
        $product = Product::factory()->create(['name' => 'Widget']);

        $this->getJson("/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Widget');
    }

    public function testShowReturns404ForMissingProduct(): void
    {
        $this->getJson('/products/non-existent-id')
            ->assertNotFound();
    }

    public function testStoreRequiresAdminRole(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/products', ['name' => 'Widget', 'price' => 9.99, 'category_id' => $category->id, 'sku' => 'SKU-001'], [
            'X-User-Id' => 'user-123',
        ])->assertForbidden();
    }

    public function testStoreCreatesProduct(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/products', [
            'name'        => 'New Widget',
            'price'       => '19.99',
            'category_id' => $category->id,
            'sku'         => 'WIDGET-001',
        ], [
            'X-User-Id'   => 'admin-123',
            'X-User-Role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Widget')
            ->assertJsonPath('data.sku', 'WIDGET-001');
    }

    public function testDestroyRequiresAdminRole(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/products/{$product->id}", [], ['X-User-Id' => 'user-123'])
            ->assertForbidden();
    }
}