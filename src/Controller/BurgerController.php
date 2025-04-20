<?php

namespace App\Controller;

use App\Entity\Burger;
use App\Repository\BurgerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/burgers')]
class BurgerController extends AbstractController
{
    #[Route('', name: 'burger_list', methods: ['GET'])]
    #[OA\Get(
        tags: ['Burgers'],
        summary: 'Liste tous les burgers',
        responses: [
            new OA\Response(response: 200, description: "Liste des burgers")
        ]
    )]
    public function list(BurgerRepository $burgerRepository): JsonResponse
    {
        $burgers = $burgerRepository->findBy(['archived' => false]);
        
        $burgersData = [];
        foreach ($burgers as $burger) {
            $burgersData[] = [
                'id' => $burger->getId(),
                'name' => $burger->getName(),
                'description' => $burger->getDescription(),
                'price' => $burger->getPrice(),
                'image' => $burger->getImage()
            ];
        }
        
        return $this->json($burgersData);
    }
    
    #[Route('', name: 'burger_create', methods: ['POST'])]
    #[OA\Post(
        tags: ['Burgers'],
        summary: 'Ajoute un nouveau burger',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "price"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Cheeseburger"),
                    new OA\Property(property: "description", type: "string", example: "Délicieux burger au fromage"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 8.50),
                    new OA\Property(property: "image", type: "string", example: "burger.jpg")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Burger créé"),
            new OA\Response(response: 400, description: "Données invalides")
        ]
    )]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['price'])) {
            return $this->json(['message' => 'Le nom et le prix sont requis'], Response::HTTP_BAD_REQUEST);
        }
        
        $burger = new Burger();
        $burger->setName($data['name']);
        $burger->setPrice($data['price']);
        $burger->setArchived(false);
        
        if (isset($data['description'])) {
            $burger->setDescription($data['description']);
        }
        
        if (isset($data['image'])) {
            $burger->setImage($data['image']);
        }
        
        $errors = $validator->validate($burger);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->persist($burger);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Burger créé avec succès',
            'burger' => [
                'id' => $burger->getId(),
                'name' => $burger->getName(),
                'description' => $burger->getDescription(),
                'price' => $burger->getPrice(),
                'image' => $burger->getImage()
            ]
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/{id}', name: 'burger_update', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Burgers'],
        summary: 'Modifie un burger existant',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "image", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Burger modifié"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 404, description: "Burger non trouvé")
        ]
    )]
    public function update(
        int $id, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        BurgerRepository $burgerRepository
    ): JsonResponse {
        $burger = $burgerRepository->find($id);
        
        if (!$burger) {
            return $this->json(['message' => 'Burger non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $burger->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $burger->setDescription($data['description']);
        }
        
        if (isset($data['price'])) {
            $burger->setPrice($data['price']);
        }
        
        if (isset($data['image'])) {
            $burger->setImage($data['image']);
        }
        
        $errors = $validator->validate($burger);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Burger mis à jour avec succès',
            'burger' => [
                'id' => $burger->getId(),
                'name' => $burger->getName(),
                'description' => $burger->getDescription(),
                'price' => $burger->getPrice(),
                'image' => $burger->getImage()
            ]
        ]);
    }
    
    #[Route('/{id}/archive', name: 'burger_archive', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Burgers'],
        summary: 'Archive un burger',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Burger archivé"),
            new OA\Response(response: 404, description: "Burger non trouvé")
        ]
    )]
    public function archive(
        int $id, 
        EntityManagerInterface $entityManager,
        BurgerRepository $burgerRepository
    ): JsonResponse {
        $burger = $burgerRepository->find($id);
        
        if (!$burger) {
            return $this->json(['message' => 'Burger non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $burger->setArchived(true);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Burger archivé avec succès'
        ]);
    }
}