<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Service métier lié aux utilisateurs : listing, préparation des données pour la vue de connexion.
 * Centralise la logique pour garder le UserController "maigre".
 */
final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Retourne tous les utilisateurs (pour la liste d'administration).
     *
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Prépare les données nécessaires à l'affichage du formulaire de connexion
     * (dernier identifiant saisi, erreur d'authentification).
     *
     * @return array{last_username: string, error: \Symfony\Component\Security\Core\Exception\AuthenticationException|null}
     */
    public function getLoginViewData(AuthenticationUtils $authenticationUtils): array
    {
        return [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ];
    }
}
