<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée des utilisateurs de démo (rôles différents) et des événements cohérents :
 * certains utilisateurs n'ont créé aucun événement, d'autres en ont plusieurs.
 */
#[AsCommand(
    name: 'app:seed:demo',
    description: 'Crée des utilisateurs de test (admin + utilisateurs) et des événements avec répartition réaliste des créateurs.',
)]
final class SeedDemoCommand extends Command
{
    /** Définition des utilisateurs de démo : email, mot de passe, prénom, nom, rôle (ROLE_ADMIN ou ROLE_USER) */
    private const DEMO_USERS = [
        ['admin@demo.local', 'admin', 'Admin', 'SecureEvents', ['ROLE_ADMIN']],
        ['marie.dupont@demo.local', 'demo', 'Marie', 'Dupont', []],
        ['jean.martin@demo.local', 'demo', 'Jean', 'Martin', []],
        ['sophie.bernard@demo.local', 'demo', 'Sophie', 'Bernard', []],
        ['pierre.leroy@demo.local', 'demo', 'Pierre', 'Leroy', []],
        ['lucie.petit@demo.local', 'demo', 'Lucie', 'Petit', []],
    ];

    /** Titres d'événements pour le seed */
    private const TITLES = [
        'Conférence Symfony 2025', 'Atelier Twig', 'Meetup PHP Lyon', 'Formation Doctrine',
        'Hackathon weekend', 'Soirée networking', 'Webinaire sécurité', 'Journée UX/UI',
        'Sprint API Platform', 'Coding dojo JS', 'Retraite produit', 'Séminaire microservices',
        'Workshop tests', 'Présentation projet', 'Team building', 'Formation Git',
        'Réunion planning Q2', 'Démo client', 'Réunion technique', 'Formation Docker',
    ];

    private const LOCATIONS = [
        'Paris - Salle A', 'Lyon - Part-Dieu', 'Marseille - Campus', 'Toulouse - Hub',
        'Bordeaux - La Cité', 'Nantes - Creative Factory', 'Lille - Euratechnologies',
        'Strasbourg - Shadok', 'Montpellier - Cap Omega', 'Remote - Visio',
    ];

    private const DESCRIPTIONS = [
        'Événement incontournable pour tous les passionnés.',
        'Venez découvrir les bonnes pratiques et échanger.',
        'Session pratique avec des exercices concrets.',
    ];

    /**
     * Répartition des créateurs par index d'utilisateur (0 = admin, 1-5 = users).
     * Tout événement a un créateur. Certains utilisateurs (ex: index 4) n'ont aucun événement.
     */
    private const CREATOR_DISTRIBUTION = [
        0, 1, 1, 2, 0, 1, 2, 3, 0, 0, 1, 2, 0, 3, 1, 5, 0, 2, 0, 1, 0, 3, 2, 1, 0,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Recréer les utilisateurs de démo s\'ils existent (réinitialise le mot de passe)')
            ->addOption('events', null, InputOption::VALUE_OPTIONAL, 'Nombre d\'événements à créer (répartition des créateurs adaptée)', 25);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $eventCount = (int) $input->getOption('events');

        $io->title('Seed démo : utilisateurs et événements');

        // 1. Créer ou récupérer les utilisateurs
        $users = [];
        foreach (self::DEMO_USERS as $index => [$email, $plainPassword, $firstName, $lastName, $roles]) {
            $existing = $this->userRepository->findOneBy(['email' => $email]);
            if ($existing !== null && !$force) {
                $users[$index] = $existing;
                $io->text(sprintf('  Utilisateur existant : %s (%s %s)', $email, $firstName, $lastName));
            } else {
                $user = $existing ?? new User();
                $user->setEmail($email);
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setRoles($roles);
                $this->entityManager->persist($user);
                $users[$index] = $user;
                $io->text(sprintf('  Créé / mis à jour : %s (%s %s) — %s', $email, $firstName, $lastName, $roles === ['ROLE_ADMIN'] ? 'admin' : 'user'));
            }
        }
        $this->entityManager->flush();

        // 2. Créer les événements avec la répartition des créateurs
        $baseDistribution = self::CREATOR_DISTRIBUTION;
        $distribution = [];
        for ($i = 0; $i < $eventCount; $i++) {
            $distribution[] = $baseDistribution[$i % \count($baseDistribution)];
        }

        $titles = self::TITLES;
        $locations = self::LOCATIONS;
        $descriptions = self::DESCRIPTIONS;
        $today = new \DateTimeImmutable('today');

        for ($i = 0; $i < $eventCount; $i++) {
            $event = new Event();
            $event->setTitle($titles[$i % \count($titles)] . ' #' . ($i + 1));
            $event->setDescription($descriptions[$i % \count($descriptions)]);
            $event->setLocation($locations[$i % \count($locations)]);
            $event->setMaxCapacity(10 + ($i % 91));
            $event->setStartDate($today->modify(sprintf('+%d days', $i)));
            $event->setIsPublished($i % 5 !== 2 && $i % 5 !== 3);

            $creatorIndex = $distribution[$i];
            $event->setCreatedBy($users[$creatorIndex] ?? $users[0]);

            $this->entityManager->persist($event);
        }
        $this->entityManager->flush();

        $io->success([
            sprintf('%d utilisateur(s) de démo prêts.', \count($users)),
            sprintf('%d événement(s) créés avec répartition réaliste des créateurs.', $eventCount),
            'Certains utilisateurs n\'ont créé aucun événement (ex: Pierre Leroy, Lucie Petit selon la répartition).',
        ]);
        $io->table(
            ['Email', 'Mot de passe', 'Rôle'],
            [
                ['admin@demo.local', 'admin', 'Admin'],
                ['marie.dupont@demo.local', 'demo', 'User'],
                ['jean.martin@demo.local', 'demo', 'User'],
                ['sophie.bernard@demo.local', 'demo', 'User'],
                ['pierre.leroy@demo.local', 'demo', 'User'],
                ['lucie.petit@demo.local', 'demo', 'User'],
            ]
        );

        return Command::SUCCESS;
    }
}
