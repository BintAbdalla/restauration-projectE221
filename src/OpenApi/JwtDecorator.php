<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;

final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        
        $schemas = $openApi->getComponents()->getSchemas();
        
        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);
        
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'user@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'password',
                ],
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: null,
            summary: 'Connexion utilisateur',
            description: 'Génère un JWT pour authentifier l\'utilisateur',
            post: new Model\Operation(
                operationId: 'postCredentialsItem',
                tags: ['Auth'],
                responses: [
                    '200' => [
                        'description' => 'Authentification réussie',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Identifiants invalides',
                    ],
                ],
                summary: 'Obtenir un JWT',
                requestBody: new Model\RequestBody(
                    description: 'Identifiants de connexion',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ]),
                ),
                security: [],
            ),
        );

        $openApi->getPaths()->addPath('/api/login', $pathItem);

        // Configurez le préfixe "Bearer " pour les appels d'API authentifiés
        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?? [];
        $securitySchemes['JWT'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);
        $openApi->getComponents()->setSecuritySchemes($securitySchemes);

        return $openApi;
    }
}