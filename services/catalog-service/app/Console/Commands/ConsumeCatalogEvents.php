<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EventConsumerService;
use Illuminate\Console\Command;

final class ConsumeCatalogEvents extends Command
{
    protected $signature   = 'app:consume-catalog-events';
    protected $description = 'Consume order.cancelled events and restock products';

    public function handle(EventConsumerService $consumer): int
    {
        $this->info('Listening for order.cancelled events...');

        $consumer->consume();

        return self::SUCCESS;
    }
}