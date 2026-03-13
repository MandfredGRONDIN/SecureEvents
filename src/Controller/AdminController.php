<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Point d'entrée de la zone d'administration (CDC : URL /admin, 403 pour non-admin).
 * Redirige vers le back-office (liste des utilisateurs).
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    /**
     * Redirection vers le back-office (gestion des utilisateurs).
     */
    #[Route(name: 'app_admin_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_user_index');
    }
}
