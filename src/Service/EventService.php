<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier des événements : listing, création, détail, réservation, édition, suppression.
 * Centralise la logique pour garder l'EventController "maigre".
 */
final class EventService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Données pour la liste paginée et filtrée des événements visibles par l'utilisateur.
     *
     * @param array<string, string> $filters
     * @return array{events: Event[], total: int, total_pages: int}
     */
    public function getListData(?User $user, array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $result = $this->eventRepository->findVisibleForUserWithFiltersPaginated($user, $filters, $page, $perPage);
        $total = $result['total'];
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $categories = $this->categoryRepository->findAllOrderedByName();

        return [
            'events' => $result['events'],
            'categories' => $categories,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Crée et persiste un nouvel événement (créateur déjà assigné sur l'entité).
     */
    public function createEvent(Event $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    /**
     * Données pour l'affichage du détail d'un événement (réservation de l'utilisateur, possibilité de réserver).
     *
     * @return array{userReservation: Reservation|null, canReserve: bool}
     */
    public function getShowViewData(Event $event, ?User $user): array
    {
        $userReservation = $user instanceof User
            ? $this->reservationRepository->findOneByEventAndParticipant($event, $user)
            : null;

        $placesRestantes = $event->getMaxCapacity() - $event->getReservations()->count();
        $canReserve = $user instanceof User
            && $userReservation === null
            && $placesRestantes > 0;

        return [
            'userReservation' => $userReservation,
            'canReserve' => $canReserve,
        ];
    }

    /**
     * Réserve une place pour l'utilisateur sur l'événement.
     *
     * @return 'success'|'already_reserved'|'event_full'
     */
    public function reserve(Event $event, User $user): string
    {
        $existing = $this->reservationRepository->findOneByEventAndParticipant($event, $user);
        if ($existing !== null) {
            return 'already_reserved';
        }

        if ($event->getReservations()->count() >= $event->getMaxCapacity()) {
            return 'event_full';
        }

        $reservation = new Reservation();
        $reservation->setParticipant($user);
        $reservation->setEvent($event);
        $reservation->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return 'success';
    }

    /**
     * Met à jour l'événement (après validation du formulaire).
     */
    public function updateEvent(Event $event): void
    {
        $this->entityManager->flush();
    }

    /**
     * Supprime l'événement et toutes les réservations qui y sont liées (contrainte FK).
     */
    public function deleteEvent(Event $event): void
    {
        foreach ($event->getReservations() as $reservation) {
            $this->entityManager->remove($reservation);
        }
        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }

    /**
     * Retourne les événements futurs et publiés (pour l'API GET /api/events).
     *
     * @return Event[]
     */
    public function getFuturePublishedEvents(): array
    {
        return $this->eventRepository->findFuturePublished();
    }
}
