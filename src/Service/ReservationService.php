<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier des réservations : liste par utilisateur, création, suppression, vérification de propriété.
 * Centralise la logique pour garder le ReservationController "maigre".
 */
final class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Retourne les réservations d'un utilisateur (événements auxquels il participe).
     *
     * @return Reservation[]
     */
    public function getReservationsForUser(User $user): array
    {
        return $this->reservationRepository->findByParticipant($user);
    }

    /**
     * Vérifie que l'utilisateur est bien le participant de la réservation.
     */
    public function isOwner(Reservation $reservation, User $user): bool
    {
        return $reservation->getParticipant() === $user;
    }

    /**
     * Crée une réservation après vérification (événement renseigné, pas de doublon, capacité).
     *
     * @return 'success'|'select_event'|'already_reserved'|'event_full'
     */
    public function createReservation(Reservation $reservation, User $user): string
    {
        $event = $reservation->getEvent();
        if ($event === null) {
            return 'select_event';
        }

        $existing = $this->reservationRepository->findOneByEventAndParticipant($event, $user);
        if ($existing !== null) {
            return 'already_reserved';
        }

        if ($event->getReservations()->count() >= $event->getMaxCapacity()) {
            return 'event_full';
        }

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return 'success';
    }

    /**
     * Supprime une réservation.
     */
    public function deleteReservation(Reservation $reservation): void
    {
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();
    }
}
