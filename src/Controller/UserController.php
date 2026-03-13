<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur HTTP pour les utilisateurs : délègue au UserService et rend les vues.
 * Responsable uniquement de la réception des requêtes et de la construction des réponses.
 */
#[Route('/user')]
final class UserController extends AbstractController
{
    /**
     * Liste des utilisateurs (back-office admin).
     */
    #[Route(name: 'app_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserService $userService): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userService->getAllUsers(),
        ]);
    }

    /**
     * Fiche d'un utilisateur (admin) : détail et action "Réinitialiser le mot de passe".
     */
    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Déclenche l'envoi d'un email de réinitialisation de mot de passe à l'utilisateur (admin).
     */
    #[Route('/{id}/reset-password', name: 'app_user_reset_password', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetPassword(Request $request, User $user, ResetPasswordService $resetPasswordService): Response
    {
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('user_reset_password_' . $user->getId(), $token)) {
            $this->addFlash('error', 'flash.csrf_invalid');
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        $resetPasswordService->requestResetForUser($user);
        $this->addFlash('success', 'flash.reset_password_email_sent_admin');

        return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * Affiche le formulaire de connexion (POST géré par le firewall).
     */
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, UserService $userService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $response = $this->render('security/login.html.twig', $userService->getLoginViewData($authenticationUtils));

        // Éviter le cache pour que le token CSRF soit toujours frais
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    /**
     * Déconnexion : gérée par le firewall (path /user/logout).
     */
    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException(
            'Cette méthode peut rester vide - elle est interceptée par la clé logout du firewall.'
        );
    }
}
