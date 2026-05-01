<?php

declare(strict_types=1);

namespace App\Services;

use Microcommerce\Common\Events\UserRegisteredEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class EventConsumerService
{
    private const EXCHANGE = 'events';
    private const QUEUE    = 'user-service.user.registered';

    public function __construct(
        private string $rabbitmqUrl,
        private ProfileService $profileService,
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
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'user.registered');

        $channel->basic_consume(
            queue: self::QUEUE,
            callback: function (AMQPMessage $message): void {
                $this->handleMessage($message);
            },
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
            $data  = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
            $event = UserRegisteredEvent::fromArray($data['payload'] ?? $data);

            $this->profileService->createFromEvent($event->userId);

            $message->ack();
        } catch (\Throwable $e) {
            // Nack without requeue on unrecoverable errors to avoid poison pills
            $message->nack(requeue: false);
            throw $e;
        }
    }
}