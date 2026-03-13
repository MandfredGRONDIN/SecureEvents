<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findVisibleForUser($user),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event, ReservationRepository $reservationRepository): Response
    {
        if (!$this->isGranted('EVENT_VIEW', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $user = $this->getUser();
        $userReservation = $user instanceof User
            ? $reservationRepository->findOneByEventAndParticipant($event, $user)
            : null;
        $placesRestantes = $event->getMaxCapacity() - $event->getReservations()->count();
        $canReserve = $user instanceof User
            && $userReservation === null
            && $placesRestantes > 0;

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'userReservation' => $userReservation,
            'canReserve' => $canReserve,
        ]);
    }

    /**
     * Réserver une place pour l'événement (utilisateur connecté).
     * Vérifie qu'il n'a pas déjà réservé et qu'il reste des places.
     */
    #[Route('/{id}/reserve', name: 'app_event_reserve', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reserve(Request $request, Event $event, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        if (!$this->isGranted('EVENT_VIEW', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('event_reserve_' . $event->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        $existing = $reservationRepository->findOneByEventAndParticipant($event, $user);
        if ($existing !== null) {
            $this->addFlash('error', 'Vous avez déjà une réservation pour cet événement.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($event->getReservations()->count() >= $event->getMaxCapacity()) {
            $this->addFlash('error', 'Cet événement est complet.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        $reservation = new Reservation();
        $reservation->setParticipant($user);
        $reservation->setEvent($event);
        $reservation->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a bien été enregistrée.');
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('EVENT_EDIT', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('EVENT_DELETE', $event)) {
            throw new NotFoundHttpException('Événement non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
