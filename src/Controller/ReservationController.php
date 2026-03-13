<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
final class ReservationController extends AbstractController
{
    /**
     * Liste des réservations de l'utilisateur connecté (événements auxquels il participe).
     */
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findByParticipant($user),
        ]);
    }

    /**
     * Création d'une réservation (le participant est l'utilisateur connecté).
     * Un même utilisateur ne peut réserver qu'une fois par événement ; la capacité max est vérifiée.
     */
    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, TranslatorInterface $translator): Response
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
            $event = $reservation->getEvent();
            if ($event === null) {
                $this->addFlash('error', $translator->trans('flash.select_event'));
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }
            $existing = $reservationRepository->findOneByEventAndParticipant($event, $user);
            if ($existing !== null) {
                $this->addFlash('error', $translator->trans('flash.already_reserved'));
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }
            if ($event->getReservations()->count() >= $event->getMaxCapacity()) {
                $this->addFlash('error', $translator->trans('flash.event_full'));
                return $this->render('reservation/new.html.twig', ['reservation' => $reservation, 'form' => $form]);
            }
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.reservation_saved_short'));
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
    public function show(Reservation $reservation): Response
    {
        $this->denyAccessUnlessReservationOwner($reservation);

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Suppression d'une réservation (réservé au participant).
     * Les réservations ne sont pas modifiables, uniquement supprimables.
     */
    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessReservationOwner($reservation);

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Vérifie que l'utilisateur connecté est bien le participant de la réservation.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    private function denyAccessUnlessReservationOwner(Reservation $reservation): void
    {
        $user = $this->getUser();
        if (!$user instanceof User || $reservation->getParticipant() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos propres réservations.');
        }
    }
}
