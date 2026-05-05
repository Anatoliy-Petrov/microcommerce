<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CatalogClient;
use App\Services\EventPublisherService;
use Mockery\MockInterface;
use Tests\TestCase;

final class CartControllerTest extends TestCase
{
    private string $userId = 'user-abc-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(CatalogClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getProduct')->andReturn(['name' => 'iPhone', 'price' => '999.00']);
        });

        $this->mock(EventPublisherService::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')->byDefault();
        });
    }

    public function testShowReturnsEmptyCart(): void
    {
        $this->getJson("/carts/{$this->userId}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.userId', $this->userId)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', '0.00');
    }

    public function testAddItemAddsProductToCart(): void
    {
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1'], $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.productId', 'prod-1')
            ->assertJsonPath('data.items.0.quantity', 1);
    }

    public function testAddItemIncrementsQuantityIfAlreadyExists(): void
    {
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1', 'quantity' => 2], $this->headers());
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1', 'quantity' => 3], $this->headers());

        $response = $this->getJson("/carts/{$this->userId}", $this->headers());
        $response->assertJsonPath('data.items.0.quantity', 5);
    }

    public function testUpdateItemChangesQuantity(): void
    {
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1'], $this->headers());
        $this->putJson("/carts/{$this->userId}/items/prod-1", ['quantity' => 4], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 4);
    }

    public function testRemoveItemDeletesFromCart(): void
    {
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1'], $this->headers());
        $this->deleteJson("/carts/{$this->userId}/items/prod-1", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }

    public function testClearCartEmptiesAll(): void
    {
        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1'], $this->headers());
        $this->deleteJson("/carts/{$this->userId}", [], $this->headers())->assertNoContent();
    }

    public function testCheckoutPublishesEventAndClearsCart(): void
    {
        $this->mock(EventPublisherService::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(\Mockery::type(\Microcommerce\Common\Events\CartCheckoutRequestedEvent::class));
        });

        $this->postJson("/carts/{$this->userId}/items", ['product_id' => 'prod-1'], $this->headers());
        $this->postJson("/carts/{$this->userId}/checkout", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.userId', $this->userId);

        $this->getJson("/carts/{$this->userId}", $this->headers())
            ->assertJsonPath('data.items', []);
    }

    public function testCheckoutFailsOnEmptyCart(): void
    {
        $this->postJson("/carts/{$this->userId}/checkout", [], $this->headers())
            ->assertUnprocessable();
    }

    public function testRequiresOwnership(): void
    {
        $this->getJson("/carts/{$this->userId}", ['X-User-Id' => 'other-user'])
            ->assertForbidden();
    }

    private function headers(): array
    {
        return ['X-User-Id' => $this->userId];
    }
}
