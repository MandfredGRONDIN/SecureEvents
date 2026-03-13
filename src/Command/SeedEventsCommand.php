<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour créer de nombreux événements de test (publiés, non publiés, avec/sans créateur).
 */
#[AsCommand(
    name: 'app:events:seed',
    description: 'Crée de nombreux événements de test pour vérifier la visibilité (anonyme, utilisateur, admin).',
)]
final class SeedEventsCommand extends Command
{
    /** Données factices pour titres, lieux et descriptions */
    private const TITLES = [
        'Conférence Symfony 2025',
        'Atelier découverte Twig',
        'Meetup PHP Lyon',
        'Formation Doctrine avancée',
        'Hackathon weekend',
        'Soirée networking développeurs',
        'Webinaire sécurité applicative',
        'Journée UX/UI design',
        'Sprint découverte API Platform',
        'Coding dojo JavaScript',
        'Retraite équipe produit',
        'Séminaire architecture microservices',
        'Workshop tests automatisés',
        'Présentation nouveau projet',
        'Team building annuel',
        'Formation Git avancé',
        'Réunion planning Q2',
        'Démo client final',
        'Réunion technique hebdo',
        'Formation Docker & Kubernetes',
    ];

    private const LOCATIONS = [
        'Paris - Salle A',
        'Lyon - Espace Part-Dieu',
        'Marseille - Campus numérique',
        'Toulouse - Hub startup',
        'Bordeaux - La Cité',
        'Nantes - Creative Factory',
        'Lille - Euratechnologies',
        'Strasbourg - Shadok',
        'Montpellier - Cap Omega',
        'Remote - Visio',
    ];

    private const DESCRIPTIONS = [
        'Un événement incontournable pour tous les passionnés.',
        'Venez découvrir les bonnes pratiques et échanger avec la communauté.',
        'Session pratique avec des exercices concrets.',
        'Idéal pour monter en compétence rapidement.',
        'Événement convivial et format court.',
    ];

    /** Noms des catégories à créer si aucune n'existe (seed). */
    private const CATEGORY_NAMES = [
        'Conférence',
        'Workshop',
        'Meetup',
        'Formation',
        'Hackathon',
        'Networking',
        'Webinaire',
        'Team building',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Nombre d\'événements à créer', 25);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');

        $io->title('Création d\'événements de test');

        $users = $this->userRepository->findBy([], ['id' => 'ASC']);
        if ($users === []) {
            $io->warning('Aucun utilisateur en base. Créez-en au moins un (ex: make db-reset) puis relancez la commande.');
            return Command::FAILURE;
        }

        $categories = $this->getOrCreateSeedCategories();
        $titles = self::TITLES;
        $locations = self::LOCATIONS;
        $descriptions = self::DESCRIPTIONS;
        $today = new \DateTimeImmutable('today');

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $event = new Event();
            $event->setTitle($titles[$i % \count($titles)] . ' #' . ($i + 1));
            $event->setDescription($descriptions[$i % \count($descriptions)]);
            $event->setLocation($locations[$i % \count($locations)]);
            $event->setMaxCapacity(10 + ($i % 91)); // 10 à 100
            $event->setStartDate($today->modify(sprintf('+%d days', $i % 120))); // sur ~4 mois

            // Répartition : ~60 % publiés, ~40 % non publiés
            $event->setIsPublished($i % 5 !== 2 && $i % 5 !== 3);

            // Tout événement a un créateur (obligatoire)
            $event->setCreatedBy($users[$i % \count($users)]);

            // Associer une catégorie (environ 85 % des événements en ont une, pour varier)
            if ($categories !== [] && $i % 7 !== 5) {
                $event->setCategory($categories[$i % \count($categories)]);
            }

            $this->entityManager->persist($event);
            $created++;
        }

        $this->entityManager->flush();

        $io->success([
            sprintf('%d événement(s) créé(s).', $created),
            'Répartition : mélange de publiés / non publiés, créateurs différents et catégories pour tester la visibilité.',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Retourne les catégories existantes, ou les crée à partir de CATEGORY_NAMES si aucune n'existe.
     *
     * @return list<Category>
     */
    private function getOrCreateSeedCategories(): array
    {
        $existing = $this->categoryRepository->findAllOrderedByName();
        if ($existing !== []) {
            return $existing;
        }
        foreach (self::CATEGORY_NAMES as $name) {
            $category = new Category();
            $category->setName($name);
            $category->computeSlug();
            $this->entityManager->persist($category);
        }
        $this->entityManager->flush();
        return $this->categoryRepository->findAllOrderedByName();
    }
}
