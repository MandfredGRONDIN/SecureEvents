<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LocaleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur HTTP du changement de langue : délègue au LocaleService, construit la redirection.
 */
final class LocaleController extends AbstractController
{
    /**
     * Bascule la locale puis redirige vers la page précédente ou l'accueil.
     */
    #[Route('/locale/{_locale}', name: 'app_locale_switch', requirements: ['_locale' => 'fr|en'], methods: ['GET'])]
    public function switch(Request $request, string $_locale, LocaleService $localeService): Response
    {
        $localeService->setLocale($request->getSession(), $_locale);

        $referer = $request->headers->get('Referer');
        if ($referer !== null && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_event_index');
    }
}
