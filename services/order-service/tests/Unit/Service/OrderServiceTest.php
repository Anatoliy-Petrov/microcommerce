<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\EventPublisherService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Microcommerce\Common\Events\CartCheckoutRequestedEvent;
use Microcommerce\Common\Events\OrderConfirmedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class OrderServiceTest extends TestCase
{
    private OrderRepository&MockObject      $orderRepository;
    private EntityManagerInterface&MockObject $em;
    private WorkflowInterface&MockObject    $workflow;
    private EventPublisherService&MockObject $eventPublisher;
    private OrderService $service;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->em              = $this->createMock(EntityManagerInterface::class);
        $this->workflow        = $this->createMock(WorkflowInterface::class);
        $this->eventPublisher  = $this->createMock(EventPublisherService::class);

        $this->service = new OrderService(
            $this->orderRepository,
            $this->em,
            $this->workflow,
            $this->eventPublisher,
        );
    }

    public function testCreateFromCheckoutPersistsOrder(): void
    {
        $event = new CartCheckoutRequestedEvent(
            userId:      'user-123',
            items:       [['productId' => 'prod-1', 'name' => 'iPhone', 'price' => '999.00', 'quantity' => 1]],
            total:       '999.00',
            requestedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        );

        $this->orderRepository->expects($this->once())->method('save');

        $order = $this->service->createFromCheckout($event);

        $this->assertSame('user-123', $order->getUserId());
        $this->assertSame(OrderStatus::Pending->value, $order->getStatus());
        $this->assertCount(1, $order->getItems());
    }

    public function testConfirmAppliesWorkflowAndPublishesEvent(): void
    {
        $order = new Order('order-id', 'user-id', '100.00', '100.00');

        $this->orderRepository->method('findByIdWithItems')->willReturn($order);
        $this->workflow->expects($this->once())->method('apply')->with($order, 'confirm');
        $this->eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(OrderConfirmedEvent::class));

        $this->service->confirm('order-id');
    }

    public function testCancelThrowsWhenNotCancellable(): void
    {
        $order = new Order('order-id', 'user-id', '100.00', '100.00');
        $order->setStatus(OrderStatus::Shipped->value);

        $this->orderRepository->method('findByIdWithItems')->willReturn($order);
        $this->workflow->method('can')->willReturn(false);

        $this->expectException(\DomainException::class);

        $this->service->cancel('order-id', 'user_request');
    }

    public function testCancelFromPendingSucceeds(): void
    {
        $order = new Order('order-id', 'user-id', '100.00', '100.00');

        $this->orderRepository->method('findByIdWithItems')->willReturn($order);
        $this->workflow->method('can')->willReturn(true);
        $this->workflow->expects($this->once())->method('apply')->with($order, 'cancel');
        $this->eventPublisher->expects($this->once())->method('publish');

        $this->service->cancel('order-id', 'user_request');
    }
}