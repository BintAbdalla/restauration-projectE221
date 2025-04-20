<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        tags: ['Auth'],
        summary: 'Connexion utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Token JWT retourné"),
            new OA\Response(response: 401, description: "Identifiants invalides")
        ]
    )]
    public function login(): Response
    {
        // Cette méthode ne sera jamais exécutée car le bundle LexikJWTAuthentication
        // intercepte la requête avant qu'elle n'atteigne ce contrôleur.
        // Ce point d'entrée est juste pour la documentation Swagger.
        return new Response('', 401);
    }
}