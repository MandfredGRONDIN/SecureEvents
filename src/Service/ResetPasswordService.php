<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ResetPasswordToken;
use App\Entity\User;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service de réinitialisation du mot de passe : création de token, envoi d'email, validation et mise à jour.
 */
final class ResetPasswordService
{
    public function __construct(
        private readonly ResetPasswordTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Demande de réinitialisation : crée un token pour l'utilisateur et envoie l'email.
     * Invalide les anciens tokens de l'utilisateur avant d'en créer un nouveau.
     */
    public function requestResetForUser(User $user): void
    {
        $this->tokenRepository->invalidateForUser($user);

        $tokenString = bin2hex(random_bytes(32));
        $tokenEntity = new ResetPasswordToken();
        $tokenEntity->setToken($tokenString);
        $tokenEntity->setUser($user);

        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate('app_reset_password', ['token' => $tokenString], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->sendResetEmail($user, $resetUrl);
    }

    /**
     * Demande par email (page "mot de passe oublié") : si l'utilisateur existe, crée le token et envoie l'email.
     * Ne révèle pas si l'email existe ou non (même message dans tous les cas).
     */
    public function requestResetByEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            $this->requestResetForUser($user);
        }
    }

    /**
     * Envoie l'email contenant le lien de réinitialisation.
     */
    public function sendResetEmail(User $user, string $resetUrl): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
            ->subject('Réinitialisation de votre mot de passe — SecureEvents')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'reset_url' => $resetUrl,
                'expiry_hours' => 1,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Retourne l'utilisateur associé à un token valide (non expiré), ou null.
     */
    public function getUserFromToken(string $token): ?User
    {
        $tokenEntity = $this->tokenRepository->findValidByToken($token);
        return $tokenEntity?->getUser();
    }

    /**
     * Définit le nouveau mot de passe et supprime le token utilisé.
     */
    public function resetPassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->tokenRepository->invalidateForUser($user);
        $this->entityManager->flush();
    }
}
