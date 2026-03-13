<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Définit la locale de la requête à partir de la session (choix de l'utilisateur).
 * Les locales autorisées sont fr et en.
 */
final class LocaleSubscriber implements EventSubscriberInterface
{
    private const LOCALE_SESSION_KEY = '_locale';

    /** @var list<string> */
    private const ALLOWED_LOCALES = ['fr', 'en'];

    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'fr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    /**
     * Applique la locale stockée en session si elle est valide.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $locale = $request->getSession()->get(self::LOCALE_SESSION_KEY);
        if ($locale !== null && \in_array($locale, self::ALLOWED_LOCALES, true)) {
            $request->setLocale($locale);
        }
    }
}
