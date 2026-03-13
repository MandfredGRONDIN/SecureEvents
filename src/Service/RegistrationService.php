<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service métier de l'inscription : enregistrement d'un nouvel utilisateur (hash du mot de passe, persistance).
 * Centralise la logique pour garder le RegistrationController "maigre".
 */
final class RegistrationService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre un nouvel utilisateur : hash du mot de passe puis persistance.
     */
    public function registerUser(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
