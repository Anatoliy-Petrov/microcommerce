<?php

declare(strict_types=1);

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class EventConsumerService
{
    private const EXCHANGE = 'events';
    private const QUEUE    = 'catalog-service.order.cancelled';

    public function __construct(
        private string $rabbitmqUrl,
        private StockService $stockService,
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
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'order.cancelled');

        $channel->basic_consume(
            queue: self::QUEUE,
            callback: fn (AMQPMessage $msg) => $this->handleMessage($msg),
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function handleMessage(AMQPMessage $message): void
    {
        try {
            $data = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
            $payload = $data['payload'] ?? $data;

            foreach ($payload['items'] ?? [] as $item) {
                $this->stockService->release($item['productId'], (int) $item['quantity']);
            }

            $message->ack();
        } catch (\Throwable $e) {
            $message->nack(requeue: false);
            throw $e;
        }
    }
}