<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        tags: ['Auth'],
        summary: 'Inscription utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password", "nom", "prenom", "telephone"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password"),
                    new OA\Property(property: "nom", type: "string", example: "Dupont"),
                    new OA\Property(property: "prenom", type: "string", example: "Jean"),
                    new OA\Property(property: "telephone", type: "string", example: "0123456789"),
                    new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Utilisateur créé avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 409, description: "Email déjà utilisé")
        ]
    )]
    public function register(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Vérification des données requises
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['nom']) || 
            !isset($data['prenom']) || !isset($data['telephone'])) {
            return $this->json(['message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérification si l'email existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
        }
        
        // Création du nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setTelephone($data['telephone']);
        
        // Gestion des rôles (optionnel, avec vérification)
        if (isset($data['roles']) && is_array($data['roles'])) {
            // Filtre pour n'accepter que les rôles autorisés (empêche l'auto-promotion à ROLE_ADMIN)
            $allowedRoles = ['ROLE_USER']; // Ajoutez d'autres rôles autorisés si nécessaire
            $roles = array_intersect($data['roles'], $allowedRoles);
            $user->setRoles($roles);
        } else {
            // Par défaut, attribuer ROLE_USER
            $user->setRoles(['ROLE_USER']);
        }
        
        // Hashage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        // Validation des données
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        // Enregistrement dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'telephone' => $user->getTelephone(),
                'roles' => $user->getRoles()
            ]
        ], Response::HTTP_CREATED);
    }
}