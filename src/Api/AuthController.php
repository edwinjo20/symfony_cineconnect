<?php
namespace App\Api;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class AuthController extends AbstractController
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route(path: '/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
    
        if (!$username || !$password) {
            return new JsonResponse(['error' => 'Username and password are required'], Response::HTTP_BAD_REQUEST);
        }
    
        // Find the user by username
        $user = $this->userRepository->findOneByUsername($username);
        
        // Check if the user exists, has ROLE_ADMIN, and if the password is valid
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
    
        // Check if the user has the ROLE_ADMIN
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Admin access required'], Response::HTTP_FORBIDDEN);
        }
    
        // Create JWT token
        $token = $jwtManager->create($user); // Create the JWT token
    
        // Return the token
        return new JsonResponse(['token' => $token]);
    }
    
}
