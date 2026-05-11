<?php

declare(strict_types=1);

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class EventConsumerService
{
    private const EXCHANGE = 'events';
    private const QUEUE    = 'payment-service.events';

    public function __construct(
        private string $rabbitmqUrl,
        private PaymentService $paymentService,
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
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'order.confirmed');

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
            // order.confirmed arrives after payment — used to reconcile if needed
            // No action required here since payment flow is initiated by the client directly
            $message->ack();
        } catch (\Throwable $e) {
            $message->nack(requeue: false);
            throw $e;
        }
    }
}