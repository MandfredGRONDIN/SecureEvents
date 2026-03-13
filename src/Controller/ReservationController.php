<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur HTTP des réservations : délègue au ReservationService, gère autorisation et CSRF.
 */
#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
final class ReservationController extends AbstractController
{
    /**
     * Liste des réservations de l'utilisateur connecté.
     */
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationService $reservationService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationService->getReservationsForUser($user),
        ]);
    }

    /**
     * Création d'une réservation (participant = utilisateur connecté).
     */
    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ReservationService $reservationService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = new Reservation();
        $reservation->setParticipant($user);
        if ($reservation->getCreatedAt() === null) {
            $reservation->setCreatedAt(new \DateTimeImmutable());
        }
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $reservationService->createReservation($reservation, $user);

            if ($result === 'select_event') {
                $this->addFlash('error', 'flash.select_event');
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }
            if ($result === 'already_reserved') {
                $this->addFlash('error', 'flash.already_reserved');
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }
            if ($result === 'event_full') {
                $this->addFlash('error', 'flash.event_full');
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }

            $this->addFlash('success', 'flash.reservation_saved_short');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    /**
     * Affiche une réservation (réservé au participant).
     */
    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation, ReservationService $reservationService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$reservationService->isOwner($reservation, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos propres réservations.');
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Suppression d'une réservation (réservé au participant).
     */
    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, ReservationService $reservationService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$reservationService->isOwner($reservation, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos propres réservations.');
        }

        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservationService->deleteReservation($reservation);
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
