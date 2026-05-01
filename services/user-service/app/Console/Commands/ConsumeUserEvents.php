<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EventConsumerService;
use Illuminate\Console\Command;

final class ConsumeUserEvents extends Command
{
    protected $signature   = 'app:consume-user-events';
    protected $description = 'Consume user.registered events from RabbitMQ and create profiles';

    public function handle(EventConsumerService $consumer): int
    {
        $this->info('Listening for user.registered events...');

        $consumer->consume();

        return self::SUCCESS;
    }
}