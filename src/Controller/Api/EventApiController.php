<?php

namespace App\Controller\Api;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Contrôleur API REST pour les événements (endpoint destiné aux partenaires et applications mobiles).
 */
#[Route('/api')]
final class EventApiController extends AbstractController
{
    /**
     * Liste des événements futurs et publiés au format JSON.
     */
    #[Route('/events', name: 'api_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository, SerializerInterface $serializer): Response
    {
        $events = $eventRepository->findFuturePublished();

        $json = $serializer->serialize($events, 'json', [
            'groups' => ['api:event:list'],
            'json_encode_options' => \JSON_UNESCAPED_UNICODE,
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
