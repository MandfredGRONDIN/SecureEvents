<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur HTTP des événements : délègue au EventService et rend les vues.
 * Responsable de l'autorisation (EVENT_VIEW, EVENT_EDIT, EVENT_DELETE), CSRF et construction des réponses.
 */
#[Route('/event')]
final class EventController extends AbstractController
{
    private const EVENTS_PER_PAGE = 9;

    /**
     * Liste des événements visibles pour l'utilisateur, avec filtres et pagination.
     */
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventService $eventService): Response
    {
        $user = $this->getUser();
        $filters = [
            'q' => $request->query->getString('q'),
            'from_date' => $request->query->getString('from_date'),
            'to_date' => $request->query->getString('to_date'),
            'location' => $request->query->getString('location'),
            'published' => $request->query->getString('published'),
            'category' => $request->query->getString('category'),
        ];
        $page = max(1, (int) $request->query->get('page', 1));

        $data = $eventService->getListData($user, $filters, $page, self::EVENTS_PER_PAGE);

        return $this->render('event/index.html.twig', [
            'events' => $data['events'],
            'categories' => $data['categories'],
            'filters' => $filters,
            'current_page' => $page,
            'total_pages' => $data['total_pages'],
            'total_events' => $data['total'],
        ]);
    }

    /**
     * Création d'un nouvel événement (utilisateur connecté).
     */
    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EventService $eventService): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour créer un événement.');
        }

        $event = new Event();
        $event->setCreatedBy($user);
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventService->createEvent($event);

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    /**
     * Détail d'un événement (visible selon EVENT_VIEW).
     */
    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event, EventService $eventService): Response
    {
        if (!$this->isGranted('EVENT_VIEW', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $user = $this->getUser();
        $viewData = $eventService->getShowViewData($event, $user);

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'userReservation' => $viewData['userReservation'],
            'canReserve' => $viewData['canReserve'],
        ]);
    }

    /**
     * Réserver une place pour l'événement (utilisateur connecté).
     */
    #[Route('/{id}/reserve', name: 'app_event_reserve', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reserve(Request $request, Event $event, EventService $eventService): Response
    {
        if (!$this->isGranted('EVENT_VIEW', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('event_reserve_' . $event->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'flash.csrf_invalid');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        $result = $eventService->reserve($event, $user);

        if ($result === 'already_reserved') {
            $this->addFlash('error', 'flash.already_reserved');
        } elseif ($result === 'event_full') {
            $this->addFlash('error', 'flash.event_full');
        } else {
            $this->addFlash('success', 'flash.reservation_saved');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * Édition d'un événement (autorisé selon EVENT_EDIT).
     */
    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EventService $eventService): Response
    {
        if (!$this->isGranted('EVENT_EDIT', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventService->updateEvent($event);

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'un événement (autorisé selon EVENT_DELETE).
     */
    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EventService $eventService): Response
    {
        if (!$this->isGranted('EVENT_DELETE', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->getPayload()->getString('_token'))) {
            $eventService->deleteEvent($event);
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
