<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur HTTP du profil utilisateur connecté : délègue au ProfileService et rend les vues.
 * Responsable uniquement de la réception des requêtes, CSRF, flash et construction des réponses.
 */
#[Route('/profile')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de modification du profil (prénom, nom, email).
     */
    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProfileService $profileService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_event_index');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profileService->updateUser($user);
            $this->addFlash('success', 'flash.profile_updated');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Supprime le compte de l'utilisateur connecté puis déconnecte (CSRF vérifié).
     */
    #[Route('/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ProfileService $profileService,
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

        $profileService->deleteAccount($user);

        // Déconnexion : vider le token (le message flash reste en session pour la page d'accueil)
        $tokenStorage->setToken(null);

        $this->addFlash('success', 'flash.profile_deleted');

        return $this->redirectToRoute('app_event_index');
    }
}
