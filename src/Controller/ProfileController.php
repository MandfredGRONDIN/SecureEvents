<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur du profil utilisateur connecté (CDC : Participant peut modifier ses informations personnelles).
 */
#[Route('/profile')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de modification du profil (prénom, nom, email).
     */
    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_event_index');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.profile_updated');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Supprime le compte de l'utilisateur connecté : retire toutes ses réservations,
     * supprime les événements qu'il a créés (et leurs réservations), puis supprime l'utilisateur et déconnecte.
     */
    #[Route('/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_event_index');
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('profile_delete', $token)) {
            $this->addFlash('error', 'flash.csrf_invalid');

            return $this->redirectToRoute('app_profile_edit');
        }

        // Retirer l'utilisateur de tous les événements (supprimer ses réservations)
        foreach ($user->getReservations() as $reservation) {
            $entityManager->remove($reservation);
        }

        // Supprimer les événements créés par l'utilisateur (et leurs réservations)
        $createdEvents = $user->getCreatedEvents()->toArray();
        foreach ($createdEvents as $event) {
            if (!$event instanceof Event) {
                continue;
            }
            foreach ($event->getReservations() as $reservation) {
                $entityManager->remove($reservation);
            }
            $entityManager->remove($event);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        // Déconnexion : vider le token (le message flash reste en session pour la page d'accueil)
        $tokenStorage->setToken(null);

        $this->addFlash('success', 'flash.profile_deleted');

        return $this->redirectToRoute('app_event_index');
    }
}
