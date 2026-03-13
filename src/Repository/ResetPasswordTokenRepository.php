<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordToken>
 */
class ResetPasswordTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordToken::class);
    }

    /**
     * Trouve un token valide (non expiré) par sa chaîne.
     */
    public function findValidByToken(string $token): ?ResetPasswordToken
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->andWhere('t.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Supprime les tokens expirés (nettoyage).
     */
    public function deleteExpired(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Invalide tous les tokens d'un utilisateur (avant d'en créer un nouveau).
     */
    public function invalidateForUser(User $user): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
