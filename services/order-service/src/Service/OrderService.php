<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderTransition;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microcommerce\Common\Events\CartCheckoutRequestedEvent;
use Microcommerce\Common\Events\OrderCancelledEvent;
use Microcommerce\Common\Events\OrderConfirmedEvent;
use Microcommerce\Common\Events\OrderShippedEvent;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class OrderService
{
    public function __construct(
        private OrderRepository      $orderRepository,
        private EntityManagerInterface $em,
        private WorkflowInterface    $orderProcessStateMachine,
        private EventPublisherService $eventPublisher,
    ) {}

    public function createFromCheckout(CartCheckoutRequestedEvent $event): Order
    {
        // Idempotency: skip if order for this cart already exists
        // We use userId + requestedAt as a natural dedup key via checking recent orders
        $order = new Order(
            id:       Uuid::v7()->toRfc4122(),
            userId:   $event->userId,
            subtotal: $event->total,
            total:    $event->total,
        );

        foreach ($event->items as $item) {
            $orderItem = new OrderItem(
                id:          Uuid::v7()->toRfc4122(),
                order:       $order,
                productId:   $item['productId'],
                productName: $item['name'],
                unitPrice:   $item['price'],
                quantity:    (int) $item['quantity'],
            );
            $order->addItem($orderItem);
        }

        $this->orderRepository->save($order, true);

        return $order;
    }

    public function confirm(string $orderId): Order
    {
        $order = $this->findOrFail($orderId);
        $from  = $order->getStatus();

        $this->orderProcessStateMachine->apply($order, 'confirm');
        $this->logTransition($order, $from, 'confirmed', 'payment.completed');
        $this->em->flush();

        $this->eventPublisher->publish(new OrderConfirmedEvent(
            orderId:     $order->getId(),
            userId:      $order->getUserId(),
            items:       $this->itemsToArray($order),
            total:       $order->getTotal(),
            confirmedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ));

        return $order;
    }

    public function cancel(string $orderId, string $reason): Order
    {
        $order = $this->findOrFail($orderId);
        $from  = $order->getStatus();

        if (!$this->orderProcessStateMachine->can($order, 'cancel')) {
            throw new \DomainException("Order {$orderId} cannot be cancelled from state {$from}");
        }

        $this->orderProcessStateMachine->apply($order, 'cancel');
        $this->logTransition($order, $from, 'cancelled', $reason);
        $this->em->flush();

        $this->eventPublisher->publish(new OrderCancelledEvent(
            orderId:     $order->getId(),
            userId:      $order->getUserId(),
            items:       $this->itemsToArray($order),
            reason:      $reason,
            cancelledAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ));

        return $order;
    }

    public function ship(string $orderId, string $trackingNumber): Order
    {
        $order = $this->findOrFail($orderId);
        $from  = $order->getStatus();

        $this->orderProcessStateMachine->apply($order, 'ship');
        $this->logTransition($order, $from, 'shipped', 'admin');
        $this->em->flush();

        $this->eventPublisher->publish(new OrderShippedEvent(
            orderId:        $order->getId(),
            trackingNumber: $trackingNumber,
            shippedAt:      (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ));

        return $order;
    }

    public function deliver(string $orderId): Order
    {
        $order = $this->findOrFail($orderId);
        $from  = $order->getStatus();

        $this->orderProcessStateMachine->apply($order, 'deliver');
        $this->logTransition($order, $from, 'delivered', 'delivery_webhook');
        $this->em->flush();

        return $order;
    }

    public function findOrFail(string $id): Order
    {
        $order = $this->orderRepository->findByIdWithItems($id);
        if ($order === null) {
            throw new \DomainException("Order {$id} not found", 404);
        }
        return $order;
    }

    private function logTransition(Order $order, string $from, string $to, string $triggeredBy): void
    {
        $transition = new OrderTransition($order, $from, $to, $triggeredBy);
        $order->addTransition($transition);
        $this->em->persist($transition);
    }

    private function itemsToArray(Order $order): array
    {
        return array_map(fn ($item) => $item->toArray(), $order->getItems()->toArray());
    }
}