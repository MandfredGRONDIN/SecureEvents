<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

#[AsController]
final class UserController
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    #[Route('/users', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Création d’un utilisateur (POST)',
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/users/{id}', name: 'user_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Mise à jour partielle d’un utilisateur (PATCH)',
                'id' => $id,
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Suppression d’un utilisateur (DELETE)',
                'id' => $id,
            ],
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('/users/{id}', name: 'user_get', methods: ['GET'])]
    public function getOne(int $id): Response
    {
        // Données d'exemple en attendant la connexion à la base
        $user = [
            'id' => $id,
            'firstName' => 'Alice',
            'lastName' => 'Durand',
            'email' => 'alice.durand@example.com',
            'roles' => ['ROLE_USER'],
            'createdAt' => new \DateTimeImmutable('-10 days'),
        ];

        return new Response(
            $this->twig->render('user/show.html.twig', ['user' => $user]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}

