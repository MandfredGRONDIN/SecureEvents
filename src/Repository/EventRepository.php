<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'ASC');

        if ($user === null) {
            $qb->andWhere('e.isPublished = :published')
                ->setParameter('published', true);
            return $qb->getQuery()->getResult();
        }

        if ($this->isAdmin($user)) {
            return $qb->getQuery()->getResult();
        }

        $qb->andWhere('e.isPublished = :published OR e.createdBy = :user')
            ->setParameter('published', true)
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    private function isAdmin(User $user): bool
    {
        return \in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
