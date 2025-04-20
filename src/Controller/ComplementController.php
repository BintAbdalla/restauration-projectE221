<?php

namespace App\Controller;

use App\Entity\Complement;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/complements')]
class ComplementController extends AbstractController
{
    #[Route('', name: 'complement_list', methods: ['GET'])]
    #[OA\Get(
        tags: ['Compléments'],
        summary: 'Liste tous les compléments',
        responses: [
            new OA\Response(response: 200, description: "Liste des compléments")
        ]
    )]
    public function list(ComplementRepository $complementRepository): JsonResponse
    {
        $complements = $complementRepository->findBy(['archived' => false]);
        
        $complementsData = [];
        foreach ($complements as $complement) {
            $complementsData[] = [
                'id' => $complement->getId(),
                'name' => $complement->getName(),
                'description' => $complement->getDescription(),
                'price' => $complement->getPrice(),
                'image' => $complement->getImage(),
                'type' => $complement->getType()
            ];
        }
        
        return $this->json($complementsData);
    }
    
    #[Route('', name: 'complement_create', methods: ['POST'])]
    #[OA\Post(
        tags: ['Compléments'],
        summary: 'Ajoute un nouveau complément',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "price", "type"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Frites"),
                    new OA\Property(property: "description", type: "string", example: "Frites fraîches maison"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 3.50),
                    new OA\Property(property: "image", type: "string", example: "frites.jpg"),
                    new OA\Property(property: "type", type: "string", example: "SIDE", enum: ["DRINK", "SIDE", "DESSERT"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Complément créé"),
            new OA\Response(response: 400, description: "Données invalides")
        ]
    )]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['price']) || !isset($data['type'])) {
            return $this->json(['message' => 'Le nom, le prix et le type sont requis'], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérifier que le type est valide
        $validTypes = ['DRINK', 'SIDE', 'DESSERT'];
        if (!in_array($data['type'], $validTypes)) {
            return $this->json([
                'message' => 'Type invalide. Les types valides sont: ' . implode(', ', $validTypes)
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $complement = new Complement();
        $complement->setName($data['name']);
        $complement->setPrice($data['price']);
        $complement->setType($data['type']);
        $complement->setArchived(false);
        
        if (isset($data['description'])) {
            $complement->setDescription($data['description']);
        }
        
        if (isset($data['image'])) {
            $complement->setImage($data['image']);
        }
        
        $errors = $validator->validate($complement);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->persist($complement);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Complément créé avec succès',
            'complement' => [
                'id' => $complement->getId(),
                'name' => $complement->getName(),
                'description' => $complement->getDescription(),
                'price' => $complement->getPrice(),
                'image' => $complement->getImage(),
                'type' => $complement->getType()
            ]
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/{id}', name: 'complement_update', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Compléments'],
        summary: 'Modifie un complément existant',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "image", type: "string"),
                    new OA\Property(property: "type", type: "string", enum: ["DRINK", "SIDE", "DESSERT"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Complément modifié"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 404, description: "Complément non trouvé")
        ]
    )]
    public function update(
        int $id, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ComplementRepository $complementRepository
    ): JsonResponse {
        $complement = $complementRepository->find($id);
        
        if (!$complement) {
            return $this->json(['message' => 'Complément non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $complement->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $complement->setDescription($data['description']);
        }
        
        if (isset($data['price'])) {
            $complement->setPrice($data['price']);
        }
        
        if (isset($data['image'])) {
            $complement->setImage($data['image']);
        }
        
        if (isset($data['type'])) {
            // Vérifier que le type est valide
            $validTypes = ['DRINK', 'SIDE', 'DESSERT'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->json([
                    'message' => 'Type invalide. Les types valides sont: ' . implode(', ', $validTypes)
                ], Response::HTTP_BAD_REQUEST);
            }
            $complement->setType($data['type']);
        }
        
        $errors = $validator->validate($complement);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Complément mis à jour avec succès',
            'complement' => [
                'id' => $complement->getId(),
                'name' => $complement->getName(),
                'description' => $complement->getDescription(),
                'price' => $complement->getPrice(),
                'image' => $complement->getImage(),
                'type' => $complement->getType()
            ]
        ]);
    }
    
    #[Route('/{id}/archive', name: 'complement_archive', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Compléments'],
        summary: 'Archive un complément',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Complément archivé"),
            new OA\Response(response: 404, description: "Complément non trouvé")
        ]
    )]
    public function archive(
        int $id, 
        EntityManagerInterface $entityManager,
        ComplementRepository $complementRepository
    ): JsonResponse {
        $complement = $complementRepository->find($id);
        
        if (!$complement) {
            return $this->json(['message' => 'Complément non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $complement->setArchived(true);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Complément archivé avec succès'
        ]);
    }
}