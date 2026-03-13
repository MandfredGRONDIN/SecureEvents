<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service métier pour la locale de l'interface : locales autorisées, enregistrement en session.
 * Centralise la logique pour garder le LocaleController "maigre".
 */
final class LocaleService
{
    public const string SESSION_KEY = '_locale';

    /** @var list<string> */
    private const ALLOWED_LOCALES = ['fr', 'en'];

    /**
     * Indique si la locale est autorisée.
     */
    public function isAllowedLocale(string $locale): bool
    {
        return \in_array($locale, self::ALLOWED_LOCALES, true);
    }

    /**
     * Enregistre la locale en session (si autorisée).
     */
    public function setLocale(SessionInterface $session, string $locale): void
    {
        if ($this->isAllowedLocale($locale)) {
            $session->set(self::SESSION_KEY, $locale);
        }
    }
}
