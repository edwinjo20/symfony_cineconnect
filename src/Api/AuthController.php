<?php

namespace App\Api;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
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
        
        // ✅ Ensure the request sends 'email' (not 'username')
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
    
        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }
    
        // ✅ Ensure the search is done using email
        $user = $this->userRepository->findOneBy(['email' => $email]);
    
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
    
        // ✅ Generate JWT Token
        $token = $jwtManager->create($user);
    
        return new JsonResponse(['token' => $token]);
    }
    
    #[Route(path: '/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Remove the JWT Cookie
        $cookie = Cookie::create('BEARER')
            ->withValue('')
            ->withHttpOnly(true)
            ->withSecure(false)
            ->withSameSite('Strict')
            ->withPath('/')
            ->withExpires(time() - 3600); // ✅ Expire immediately

        $response = new JsonResponse(['success' => true]);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
