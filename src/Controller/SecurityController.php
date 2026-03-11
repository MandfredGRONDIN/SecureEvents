<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur gérant l'authentification (connexion).
 */
final class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion et gère les erreurs d'authentification.
     */
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirige si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        // Dernière erreur (mauvais identifiants, etc.)
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier email saisi pour pré-remplir le champ
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Déconnexion : gérée par le firewall (path /logout), cette route ne doit pas être appelée directement.
     */
    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException(
            'Cette méthode peut être vide - elle sera interceptée par la clé logout du firewall.'
        );
    }
}
