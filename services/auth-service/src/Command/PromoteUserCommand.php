<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Promote or demote a user to/from admin role',
)]
final class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addOption('demote', 'd', InputOption::VALUE_NONE, 'Demote to regular user instead of promoting');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            $io->error("No user found with email: {$email}");
            return Command::FAILURE;
        }

        $demote = (bool) $input->getOption('demote');
        $role   = $demote ? UserRole::User : UserRole::Admin;
        $user->setRole($role);
        $this->em->flush();

        $io->success(sprintf(
            'User "%s" has been %s.',
            $email,
            $demote ? 'demoted to user' : 'promoted to admin',
        ));

        return Command::SUCCESS;
    }
}