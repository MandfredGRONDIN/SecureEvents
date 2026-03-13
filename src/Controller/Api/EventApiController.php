<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Contrôleur API REST des événements : délègue au EventService, sérialise en JSON.
 */
#[Route('/api')]
final class EventApiController extends AbstractController
{
    /**
     * Liste des événements futurs et publiés au format JSON.
     */
    #[Route('/events', name: 'api_events_list', methods: ['GET'])]
    public function list(EventService $eventService, SerializerInterface $serializer): Response
    {
        $events = $eventService->getFuturePublishedEvents();

        $json = $serializer->serialize($events, 'json', [
            'groups' => ['api:event:list'],
            'json_encode_options' => \JSON_UNESCAPED_UNICODE,
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
