<?php

declare(strict_types=1);

namespace App\Service;

use Microcommerce\Common\Events\DomainEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

readonly class EventPublisherService
{
    private const EXCHANGE = 'events';

    public function __construct(private string $rabbitmqUrl) {}

    public function publish(DomainEvent $event): void
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

        try {
            $channel = $connection->channel();
            $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);

            $body    = json_encode($event->toEnvelope(), JSON_THROW_ON_ERROR);
            $message = new AMQPMessage($body, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'  => 'application/json',
            ]);

            $channel->basic_publish($message, self::EXCHANGE, $event::getName());
            $channel->close();
        } finally {
            $connection->close();
        }
    }
}