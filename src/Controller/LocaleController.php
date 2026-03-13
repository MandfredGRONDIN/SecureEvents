<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour changer la langue de l'interface (stockée en session).
 */
final class LocaleController extends AbstractController
{
    private const LOCALE_SESSION_KEY = '_locale';

    /** @var list<string> */
    private const ALLOWED_LOCALES = ['fr', 'en'];

    /**
     * Bascule la locale puis redirige vers la page précédente ou l'accueil.
     */
    #[Route('/locale/{_locale}', name: 'app_locale_switch', requirements: ['_locale' => 'fr|en'], methods: ['GET'])]
    public function switch(Request $request, string $_locale): Response
    {
        if (\in_array($_locale, self::ALLOWED_LOCALES, true)) {
            $request->getSession()->set(self::LOCALE_SESSION_KEY, $_locale);
        }

        $referer = $request->headers->get('Referer');
        if ($referer !== null && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_event_index');
    }
}
