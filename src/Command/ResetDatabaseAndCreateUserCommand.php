<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Process\Process;

/**
 * Réinitialise la base de données (drop, create, migrate) et crée un utilisateur par défaut.
 */
#[AsCommand(
    name: 'app:db:reset-with-user',
    description: 'Supprime toutes les données, recrée le schéma via les migrations et crée un utilisateur avec mot de passe hashé.',
)]
final class ResetDatabaseAndCreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'utilisateur', 'admin@admin.admin')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe en clair', 'admin')
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL, 'Prénom', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Nom', 'SecureEvents');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Réinitialisation de la base et création d\'un utilisateur');

        // 1. Drop database
        $io->section('Suppression de la base de données');
        $drop = new Process(['php', 'bin/console', 'doctrine:database:drop', '--force'], $this->kernel->getProjectDir());
        $drop->setTimeout(30);
        $drop->run();
        if ($drop->getErrorOutput() && !str_contains($drop->getErrorOutput(), 'Could not drop database') && !$drop->isSuccessful()) {
            $io->warning($drop->getErrorOutput());
        }

        // 2. Create database
        $io->section('Création de la base de données');
        $create = new Process(['php', 'bin/console', 'doctrine:database:create'], $this->kernel->getProjectDir());
        $create->setTimeout(30);
        $create->run();
        if (!$create->isSuccessful()) {
            $io->error('Échec création BDD : ' . $create->getErrorOutput());

            return Command::FAILURE;
        }

        // 3. Run migrations
        $io->section('Exécution des migrations');
        $migrate = new Process(['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction'], $this->kernel->getProjectDir());
        $migrate->setTimeout(120);
        $migrate->run();
        if (!$migrate->isSuccessful()) {
            $io->error('Échec migrations : ' . $migrate->getErrorOutput());

            return Command::FAILURE;
        }

        // 4. Create user with hashed password
        $email = (string) $input->getOption('email');
        $plainPassword = (string) $input->getOption('password');
        $firstName = (string) $input->getOption('first-name');
        $lastName = (string) $input->getOption('last-name');

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            'Base de données réinitialisée.',
            sprintf('Utilisateur créé : %s / %s', $email, $plainPassword),
        ]);

        return Command::SUCCESS;
    }
}
