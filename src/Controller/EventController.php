<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur des événements (CRUD).
 * Expose les actions GET (liste, détail), POST (création), PATCH (mise à jour), DELETE (suppression).
 */
final class EventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Liste des événements.
     */
    #[Route(path: '/events', name: 'app_events_index', methods: ['GET'])]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['startDate' => 'ASC']);

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    /**
     * Détail d'un événement par id . Retourne 404 si non trouvé.
     */
    #[Route(path: '/events/{id}', name: 'app_events_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    /**
     * Création d'un événement. Attend un corps JSON avec title, description, startDate, location, maxCapacity, isPublished.
     */
    #[Route(path: '/events', name: 'app_events_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getPayload()->all();
        $event = new Event();

        $this->hydrateEventFromArray($event, $data);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return new JsonResponse(
            ['id' => $event->getId(), 'title' => $event->getTitle()],
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('app_events_show', ['id' => $event->getId()])]
        );
    }

    /**
     * Mise à jour partielle d'un événement. 404 si non trouvé.
     */
    #[Route(path: '/events/{id}', name: 'app_events_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $data = $request->getPayload()->all();
        $this->hydrateEventFromArray($event, $data);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Suppression d'un événement. 404 si non trouvé.
     */
    #[Route(path: '/events/{id}', name: 'app_events_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if ($event === null) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Hydrate l'entité Event à partir d'un tableau (clés : title, description, startDate, location, maxCapacity, isPublished).
     */
    private function hydrateEventFromArray(Event $event, array $data): void
    {
        if (isset($data['title']) && \is_string($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $event->setDescription(\is_string($data['description']) ? $data['description'] : null);
        }
        if (isset($data['startDate']) && \is_string($data['startDate'])) {
            try {
                $event->setStartDate(new \DateTimeImmutable($data['startDate']));
            } catch (\Exception) {
                // Ignorer les dates invalides
            }
        }
        if (isset($data['location']) && \is_string($data['location'])) {
            $event->setLocation($data['location']);
        }
        if (isset($data['maxCapacity']) && \is_numeric($data['maxCapacity'])) {
            $event->setMaxCapacity((int) $data['maxCapacity']);
        }
        if (isset($data['isPublished'])) {
            $event->setIsPublished((bool) $data['isPublished']);
        }
    }
}
