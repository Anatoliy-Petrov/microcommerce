<?php

declare(strict_types=1);

namespace App\Service;

use Microcommerce\Common\Events\CartCheckoutRequestedEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class EventConsumerService
{
    private const EXCHANGE = 'events';
    private const QUEUE    = 'order-service.events';

    public function __construct(
        private string $rabbitmqUrl,
        private OrderService $orderService,
    ) {}

    public function consume(): void
    {
        $parsed = parse_url($this->rabbitmqUrl);
        $vhost  = ltrim($parsed['path'] ?? '/', '/') ?: '/';

        $connection = new AMQPStreamConnection(
            host:     $parsed['host'],
            port:     $parsed['port'] ?? 5672,
            user:     $parsed['user'],
            password: $parsed['pass'],
            vhost:    $vhost,
        );

        $channel = $connection->channel();
        $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);
        $channel->queue_declare(self::QUEUE, false, true, false, false);

        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'cart.checkout_requested');
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'payment.completed');
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'payment.failed');

        $channel->basic_consume(
            queue:    self::QUEUE,
            callback: fn (AMQPMessage $msg) => $this->handle($msg),
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function handle(AMQPMessage $message): void
    {
        try {
            $envelope  = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
            $eventName = $envelope['eventName'] ?? '';
            $payload   = $envelope['payload'] ?? $envelope;

            match ($eventName) {
                'cart.checkout_requested' => $this->handleCheckout($payload),
                'payment.completed'       => $this->handlePaymentCompleted($payload),
                'payment.failed'          => $this->handlePaymentFailed($payload),
                default                   => null, // unknown event — silently ignore
            };

            $message->ack();
        } catch (\Throwable $e) {
            $message->nack(requeue: false);
            throw $e;
        }
    }

    private function handleCheckout(array $payload): void
    {
        $event = CartCheckoutRequestedEvent::fromArray($payload);
        $this->orderService->createFromCheckout($event);
    }

    private function handlePaymentCompleted(array $payload): void
    {
        $orderId = $payload['orderId'] ?? null;
        if ($orderId === null) {
            return;
        }
        $this->orderService->confirm($orderId);
    }

    private function handlePaymentFailed(array $payload): void
    {
        $orderId = $payload['orderId'] ?? null;
        if ($orderId === null) {
            return;
        }
        $this->orderService->cancel($orderId, 'payment_failed');
    }
}