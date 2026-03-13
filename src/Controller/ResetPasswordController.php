<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\RequestResetPasswordType;
use App\Form\ResetPasswordType;
use App\Service\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Réinitialisation du mot de passe : demande par email (lien "mot de passe oublié") et définition du nouveau MDP via token.
 */
#[Route('/reset-password')]
final class ResetPasswordController extends AbstractController
{
    /**
     * Page "Mot de passe oublié" : saisie de l'email, envoi du lien si l'utilisateur existe.
     */
    #[Route('/request', name: 'app_request_reset_password', methods: ['GET', 'POST'])]
    public function request(Request $request, ResetPasswordService $resetPasswordService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $form = $this->createForm(RequestResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            $resetPasswordService->requestResetByEmail($email);
            $this->addFlash('success', 'flash.reset_password_email_sent');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/request_reset_password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Page "Définir un nouveau mot de passe" : formulaire avec token (lien reçu par email).
     */
    #[Route('/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, ResetPasswordService $resetPasswordService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $user = $resetPasswordService->getUserFromToken($token);
        if ($user === null) {
            $this->addFlash('error', 'flash.reset_password_token_invalid');
            return $this->redirectToRoute('app_request_reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $resetPasswordService->resetPassword($user, $plainPassword);
            $this->addFlash('success', 'flash.reset_password_done');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
            'token' => $token,
        ]);
    }
}
