<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\EventConsumerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:consume-order-events',
    description: 'Consume cart.checkout_requested, payment.completed, payment.failed from RabbitMQ',
)]
final class ConsumeOrderEventsCommand extends Command
{
    public function __construct(private readonly EventConsumerService $consumer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Listening for order events...');

        $this->consumer->consume();

        return Command::SUCCESS;
    }
}