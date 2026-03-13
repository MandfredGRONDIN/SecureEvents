<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Point d'entrée HTTP de la zone d'administration (CDC : URL /admin, 403 pour non-admin).
 * Affiche le tableau de bord admin et délègue au AdminService pour les données.
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    /**
     * Tableau de bord d'administration : liens vers gestion des utilisateurs et des événements.
     */
    #[Route(name: 'app_admin_index', methods: ['GET'])]
    public function index(AdminService $adminService): Response
    {
        return $this->render('admin/index.html.twig', [
            'dashboard_entries' => $adminService->getDashboardEntries(),
        ]);
    }
}
