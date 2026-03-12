<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Point d'entrée d'authentification personnalisé.
 * Redirige les utilisateurs non connectés vers la page des événements
 * au lieu de la page de connexion lorsqu'ils accèdent à une ressource protégée.
 */
final class RedirectToEventEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Redirige vers la liste des événements lorsque l'utilisateur n'est pas authentifié.
     *
     * @param Request $request La requête en cours
     * @param AuthenticationException|null $authException Exception d'authentification éventuelle
     * @return RedirectResponse Réponse de redirection vers app_event_index
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_event_index'));
    }
}
