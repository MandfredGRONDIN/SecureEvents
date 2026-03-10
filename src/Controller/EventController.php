<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;


#[AsController]
final class EventController
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }
    
    #[Route(path: '/events', name: 'app_events_index', methods: ['GET'])]
    public function index(): Response
    {
        $event = [
            'id' => 1,
            'name' => 'Conférence Sécurité 2025',
            'description' => 'Événement dédié aux bonnes pratiques de sécurité applicative.',
            'startAt' => '2025-06-15T09:00:00+02:00',
            'endAt' => '2025-06-15T18:00:00+02:00',
            'location' => 'Paris, France',
            'capacity' => 200,
        ];

        return new Response(
            $this->twig->render('event/index.html.twig', ['event' => $event]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
