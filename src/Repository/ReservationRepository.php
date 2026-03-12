<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des réservations.
 *
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Retourne les réservations d'un participant donné (événements auxquels il participe).
     *
     * @return Reservation[]
     */
    public function findByParticipant(User $participant): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.participant = :participant')
            ->setParameter('participant', $participant)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la réservation d'un participant pour un événement donné, ou null.
     */
    public function findOneByEventAndParticipant(Event $event, User $participant): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.Event = :event')
            ->andWhere('r.participant = :participant')
            ->setParameter('event', $event)
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
