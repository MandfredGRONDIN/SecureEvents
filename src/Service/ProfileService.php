<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier du profil utilisateur : mise à jour des infos, suppression de compte.
 * Centralise la logique pour garder le ProfileController "maigre".
 */
final class ProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Persiste les modifications du profil utilisateur (après validation du formulaire).
     */
    public function updateUser(User $user): void
    {
        $this->entityManager->flush();
    }

    /**
     * Supprime le compte utilisateur : retire toutes ses réservations, supprime les événements
     * qu'il a créés (et leurs réservations), puis supprime l'utilisateur.
     */
    public function deleteAccount(User $user): void
    {
        // Retirer l'utilisateur de tous les événements (supprimer ses réservations)
        foreach ($user->getReservations() as $reservation) {
            $this->entityManager->remove($reservation);
        }

        // Supprimer les événements créés par l'utilisateur (et leurs réservations)
        $createdEvents = $user->getCreatedEvents()->toArray();
        foreach ($createdEvents as $event) {
            if (!$event instanceof Event) {
                continue;
            }
            foreach ($event->getReservations() as $reservation) {
                $this->entityManager->remove($reservation);
            }
            $this->entityManager->remove($event);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
