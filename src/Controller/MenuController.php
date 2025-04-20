<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Repository\BurgerRepository;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/menus')]
class MenuController extends AbstractController
{
    #[Route('', name: 'menu_list', methods: ['GET'])]
    #[OA\Get(
        tags: ['Menus'],
        summary: 'Liste tous les menus',
        responses: [
            new OA\Response(response: 200, description: "Liste des menus")
        ]
    )]
    public function list(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findBy(['archived' => false]);
        
        $menusData = [];
        foreach ($menus as $menu) {
            $menusData[] = [
                'id' => $menu->getId(),
                'name' => $menu->getName(),
                'description' => $menu->getDescription(),
                'price' => $menu->getPrice(),
                'image' => $menu->getImage(),
                'burgers' => $menu->getBurgers()->map(fn($burger) => [
                    'id' => $burger->getId(),
                    'name' => $burger->getName()
                ])->toArray(),
                'complements' => $menu->getComplements()->map(fn($complement) => [
                    'id' => $complement->getId(),
                    'name' => $complement->getName()
                ])->toArray()
            ];
        }
        
        return $this->json($menusData);
    }
    
    #[Route('', name: 'menu_create', methods: ['POST'])]
    #[OA\Post(
        tags: ['Menus'],
        summary: 'Ajoute un nouveau menu',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "burgerIds", "complementIds"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Menu Maxi"),
                    new OA\Property(property: "description", type: "string", example: "Notre menu le plus copieux"),
                    new OA\Property(property: "image", type: "string", example: "menu.jpg"),
                    new OA\Property(property: "burgerIds", type: "array", items: new OA\Items(type: "integer"), example: [1, 2]),
                    new OA\Property(property: "complementIds", type: "array", items: new OA\Items(type: "integer"), example: [3, 4])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Menu créé"),
            new OA\Response(response: 400, description: "Données invalides")
        ]
    )]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        BurgerRepository $burgerRepository,
        ComplementRepository $complementRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['burgerIds']) || !isset($data['complementIds'])) {
            return $this->json(['message' => 'Le nom, les burgers et les compléments sont requis'], Response::HTTP_BAD_REQUEST);
        }
        
        $menu = new Menu();
        $menu->setName($data['name']);
        $menu->setArchived(false);
        
        if (isset($data['description'])) {
            $menu->setDescription($data['description']);
        }
        
        if (isset($data['image'])) {
            $menu->setImage($data['image']);
        }
        
        // Ajouter les burgers
        $totalPrice = 0;
        foreach ($data['burgerIds'] as $burgerId) {
            $burger = $burgerRepository->find($burgerId);
            if (!$burger) {
                return $this->json(['message' => "Burger avec l'ID $burgerId non trouvé"], Response::HTTP_BAD_REQUEST);
            }
            $menu->addBurger($burger);
            $totalPrice += $burger->getPrice();
        }
        
        // Ajouter les compléments
        foreach ($data['complementIds'] as $complementId) {
            $complement = $complementRepository->find($complementId);
            if (!$complement) {
                return $this->json(['message' => "Complément avec l'ID $complementId non trouvé"], Response::HTTP_BAD_REQUEST);
            }
            $menu->addComplement($complement);
            $totalPrice += $complement->getPrice();
        }
        
        // Appliquer une réduction pour le menu (exemple : 10%)
        $reduction = 0.1; // 10%
        $finalPrice = $totalPrice * (1 - $reduction);
        $menu->setPrice($finalPrice);
        
        $errors = $validator->validate($menu);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->persist($menu);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Menu créé avec succès',
            'menu' => [
                'id' => $menu->getId(),
                'name' => $menu->getName(),
                'description' => $menu->getDescription(),
                'price' => $menu->getPrice(),
                'image' => $menu->getImage(),
                'burgers' => $menu->getBurgers()->map(fn($burger) => [
                    'id' => $burger->getId(),
                    'name' => $burger->getName()
                ])->toArray(),
                'complements' => $menu->getComplements()->map(fn($complement) => [
                    'id' => $complement->getId(),
                    'name' => $complement->getName()
                ])->toArray()
            ]
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/{id}', name: 'menu_update', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Menus'],
        summary: 'Modifie un menu existant',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "image", type: "string"),
                    new OA\Property(property: "burgerIds", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "complementIds", type: "array", items: new OA\Items(type: "integer"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Menu modifié"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 404, description: "Menu non trouvé")
        ]
    )]
    public function update(
        int $id, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        MenuRepository $menuRepository,
        BurgerRepository $burgerRepository,
        ComplementRepository $complementRepository
    ): JsonResponse {
        $menu = $menuRepository->find($id);
        
        if (!$menu) {
            return $this->json(['message' => 'Menu non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $menu->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $menu->setDescription($data['description']);
        }
        
        if (isset($data['image'])) {
            $menu->setImage($data['image']);
        }
        
        // Mise à jour des burgers
        if (isset($data['burgerIds'])) {
            // Supprimer les anciens burgers
            foreach ($menu->getBurgers() as $burger) {
                $menu->removeBurger($burger);
            }
            
            // Ajouter les nouveaux burgers
            foreach ($data['burgerIds'] as $burgerId) {
                $burger = $burgerRepository->find($burgerId);
                if (!$burger) {
                    return $this->json(['message' => "Burger avec l'ID $burgerId non trouvé"], Response::HTTP_BAD_REQUEST);
                }
                $menu->addBurger($burger);
            }
        }
        
        // Mise à jour des compléments
        if (isset($data['complementIds'])) {
            // Supprimer les anciens compléments
            foreach ($menu->getComplements() as $complement) {
                $menu->removeComplement($complement);
            }
            
            // Ajouter les nouveaux compléments
            foreach ($data['complementIds'] as $complementId) {
                $complement = $complementRepository->find($complementId);
                if (!$complement) {
                    return $this->json(['message' => "Complément avec l'ID $complementId non trouvé"], Response::HTTP_BAD_REQUEST);
                }
                $menu->addComplement($complement);
            }
        }
        
        // Recalculer le prix si nécessaire
        if (isset($data['burgerIds']) || isset($data['complementIds'])) {
            $totalPrice = 0;
            
            // Calculer le prix total des burgers
            foreach ($menu->getBurgers() as $burger) {
                $totalPrice += $burger->getPrice();
            }
            
            // Calculer le prix total des compléments
            foreach ($menu->getComplements() as $complement) {
                $totalPrice += $complement->getPrice();
            }
            
            // Appliquer la réduction
            $reduction = 0.1; // 10%
            $finalPrice = $totalPrice * (1 - $reduction);
            $menu->setPrice($finalPrice);
        }
        
        $errors = $validator->validate($menu);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Menu mis à jour avec succès',
            'menu' => [
                'id' => $menu->getId(),
                'name' => $menu->getName(),
                'description' => $menu->getDescription(),
                'price' => $menu->getPrice(),
                'image' => $menu->getImage(),
                'burgers' => $menu->getBurgers()->map(fn($burger) => [
                    'id' => $burger->getId(),
                    'name' => $burger->getName()
                ])->toArray(),
                'complements' => $menu->getComplements()->map(fn($complement) => [
                    'id' => $complement->getId(),
                    'name' => $complement->getName()
                ])->toArray()
            ]
        ]);
    }
    
    #[Route('/{id}/archive', name: 'menu_archive', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Menus'],
        summary: 'Archive un menu',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Menu archivé"),
            new OA\Response(response: 404, description: "Menu non trouvé")
        ]
    )]
    public function archive(
        int $id, 
        EntityManagerInterface $entityManager,
        MenuRepository $menuRepository
    ): JsonResponse {
        $menu = $menuRepository->find($id);
        
        if (!$menu) {
            return $this->json(['message' => 'Menu non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $menu->setArchived(true);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Menu archivé avec succès'
        ]);
    }
    
    #[Route('/{id}/price', name: 'menu_calculate_price', methods: ['GET'])]
    #[OA\Get(
        tags: ['Menus'],
        summary: 'Calcule le prix d\'un menu',
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Prix calculé"),
            new OA\Response(response: 404, description: "Menu non trouvé")
        ]
    )]
    public function calculatePrice(
        int $id,
        MenuRepository $menuRepository
    ): JsonResponse {
        $menu = $menuRepository->find($id);
        
        if (!$menu) {
            return $this->json(['message' => 'Menu non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $totalPrice = 0;
        $burgerPrices = [];
        $complementPrices = [];
        
        // Calculer le prix des burgers
        foreach ($menu->getBurgers() as $burger) {
            $burgerPrices[] = [
                'id' => $burger->getId(),
                'name' => $burger->getName(),
                'price' => $burger->getPrice()
            ];
            $totalPrice += $burger->getPrice();
        }
        
        // Calculer le prix des compléments
        foreach ($menu->getComplements() as $complement) {
            $complementPrices[] = [
                'id' => $complement->getId(),
                'name' => $complement->getName(),
                'price' => $complement->getPrice()
            ];
            $totalPrice += $complement->getPrice();
        }
        
        // Calculer la réduction
        $reduction = 0.1; // 10%
        $reductionAmount = $totalPrice * $reduction;
        $finalPrice = $totalPrice - $reductionAmount;
        
        return $this->json([
            'menu' => [
                'id' => $menu->getId(),
                'name' => $menu->getName()
            ],
            'burgers' => $burgerPrices,
            'complements' => $complementPrices,
            'totalBeforeDiscount' => $totalPrice,
            'discount' => $reductionAmount,
            'discountPercentage' => $reduction * 100 . '%',
            'finalPrice' => $finalPrice
        ]);
    }
}