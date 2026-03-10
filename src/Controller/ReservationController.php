<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur des réservations (CRUD).
 * Expose les actions GET (liste, détail), POST (création), PATCH (mise à jour), DELETE (suppression).
 */
final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EventRepository $eventRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Liste des réservations (tri par date de création décroissante).
     */
    #[Route(path: '/reservations', name: 'app_reservations_index', methods: ['GET'])]
    public function index(): Response
    {
        $reservations = $this->reservationRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Détail d'une réservation par id. Retourne 404 si non trouvée.
     */
    #[Route(path: '/reservations/{id}', name: 'app_reservations_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);
        if ($reservation === null) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Création d'une réservation. Attend un corps JSON avec participant (id user), event (id event), createdAt (optionnel).
     */
    #[Route(path: '/reservations', name: 'app_reservations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getPayload()->all();
        $reservation = new Reservation();

        $this->hydrateReservationFromArray($reservation, $data);
        if ($reservation->getCreatedAt() === null) {
            $reservation->setCreatedAt(new \DateTimeImmutable());
        }
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return new JsonResponse(
            ['id' => $reservation->getId()],
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('app_reservations_show', ['id' => $reservation->getId()])]
        );
    }

    /**
     * Mise à jour partielle d'une réservation. 404 si non trouvée.
     */
    #[Route(path: '/reservations/{id}', name: 'app_reservations_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $reservation = $this->reservationRepository->find($id);
        if ($reservation === null) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $data = $request->getPayload()->all();
        $this->hydrateReservationFromArray($reservation, $data);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Suppression d'une réservation. 404 si non trouvée.
     */
    #[Route(path: '/reservations/{id}', name: 'app_reservations_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $reservation = $this->reservationRepository->find($id);
        if ($reservation === null) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Hydrate l'entité Reservation à partir d'un tableau (participant id, event id, createdAt).
     *
     * @param array<string, mixed> $data
     */
    private function hydrateReservationFromArray(Reservation $reservation, array $data): void
    {
        if (isset($data['participant']) && \is_numeric($data['participant'])) {
            $user = $this->userRepository->find((int) $data['participant']);
            if ($user instanceof User) {
                $reservation->setParticipant($user);
            }
        }
        if (isset($data['event']) && \is_numeric($data['event'])) {
            $event = $this->eventRepository->find((int) $data['event']);
            if ($event instanceof Event) {
                $reservation->setEvent($event);
            }
        }
        if (isset($data['createdAt']) && \is_string($data['createdAt'])) {
            try {
                $reservation->setCreatedAt(new \DateTimeImmutable($data['createdAt']));
            } catch (\Exception) {
                // Ignorer les dates invalides
            }
        }
    }
}
