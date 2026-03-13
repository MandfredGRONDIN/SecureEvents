<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne les événements visibles pour l'utilisateur selon son statut :
     * - Non connecté : uniquement les événements publiés
     * - Connecté (non admin) : publiés + ceux qu'il a créés (même non publiés)
     * - Admin : tous les événements
     *
     * @return Event[]
     */
    public function findVisibleForUser(?User $user): array
    {
        return $this->findVisibleForUserWithFilters($user, []);
    }

    /**
     * Retourne les événements visibles pour l'utilisateur avec filtres optionnels.
     *
     * @param array<string, string> $filters
     * @return Event[]
     */
    public function findVisibleForUserWithFilters(?User $user, array $filters): array
    {
        return $this->createQueryBuilderForVisibleWithFilters($user, $filters)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une page d'événements visibles avec filtres, et le total pour la pagination.
     *
     * @param array<string, string> $filters
     * @return array{events: Event[], total: int}
     */
    public function findVisibleForUserWithFiltersPaginated(?User $user, array $filters, int $page = 1, int $perPage = 9): array
    {
        $qb = $this->createQueryBuilderForVisibleWithFilters($user, $filters);

        $countQb = (clone $qb)->select('COUNT(e.id)')->resetDQLPart('orderBy');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $offset = $perPage * max(0, $page - 1);
        $events = $qb
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['events' => $events, 'total' => $total];
    }

    /**
     * Construit le QueryBuilder pour les événements visibles avec filtres.
     *
     * @param array<string, string> $filters
     */
    private function createQueryBuilderForVisibleWithFilters(?User $user, array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'ASC');

        if ($user === null) {
            $qb->andWhere('e.isPublished = :published')
                ->setParameter('published', true);
        } elseif (!$this->isAdmin($user)) {
            $qb->andWhere('e.isPublished = :published OR e.createdBy = :user')
                ->setParameter('published', true)
                ->setParameter('user', $user);
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $qb->andWhere('LOWER(e.title) LIKE LOWER(:q)')
                ->setParameter('q', '%' . trim($filters['q']) . '%');
        }

        if (isset($filters['from_date']) && $filters['from_date'] !== '') {
            try {
                $from = new \DateTimeImmutable($filters['from_date']);
                $qb->andWhere('e.startDate >= :from_date')
                    ->setParameter('from_date', $from);
            } catch (\Exception) {
                // Ignorer une date invalide
            }
        }

        if (isset($filters['to_date']) && $filters['to_date'] !== '') {
            try {
                $to = new \DateTimeImmutable($filters['to_date']);
                $qb->andWhere('e.startDate <= :to_date')
                    ->setParameter('to_date', $to);
            } catch (\Exception) {
                // Ignorer une date invalide
            }
        }

        if (isset($filters['location']) && $filters['location'] !== '') {
            $qb->andWhere('LOWER(e.location) LIKE LOWER(:location)')
                ->setParameter('location', '%' . trim($filters['location']) . '%');
        }

        if (isset($filters['published']) && $filters['published'] !== '') {
            $qb->andWhere('e.isPublished = :filterPublished')
                ->setParameter('filterPublished', $filters['published'] === '1');
        }

        if (isset($filters['category']) && $filters['category'] !== '') {
            if ($filters['category'] === 'none') {
                $qb->andWhere('e.category IS NULL');
            } else {
                $qb->andWhere('e.category = :categoryId')
                    ->setParameter('categoryId', (int) $filters['category']);
            }
        }

        return $qb;
    }

    /**
     * Retourne les événements publiés dont la date de début est aujourd'hui ou dans le futur.
     * Utilisé par l'API GET /api/events (liste pour partenaires / applications mobiles).
     *
     * @return Event[]
     */
    public function findFuturePublished(): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate >= :today')
            ->setParameter('published', true)
            ->setParameter('today', $today)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function isAdmin(User $user): bool
    {
        return \in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
