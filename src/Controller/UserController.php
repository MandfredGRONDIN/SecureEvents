<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function index(UserService $userService): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userService->getAllUsers(),
        ]);
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
