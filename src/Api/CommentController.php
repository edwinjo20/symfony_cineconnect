<?php

namespace App\Api;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Film;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/comments')]
final class CommentController extends AbstractController
{
    // ✅ Route to list all comments (restricted to ROLE_ADMIN)
    #[Route('', name: 'api_comment_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
    
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
    
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
    
        $comments = $entityManager->getRepository(Comment::class)->findAll();
    
        return $this->json($comments, 200, [], ['groups' => 'comment:read']);
    }
    

    // ✅ Route to approve a comment (restricted to ROLE_ADMIN)
    #[Route('/{id}/approve', name: 'api_comment_approve', methods: ['POST'])]
    public function approveComment(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied: Only admins can approve comments.'], Response::HTTP_FORBIDDEN);
        }

        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        $comment->setApproved(true);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Comment approved successfully', 'comment_id' => $comment->getId()]);
    }

    // ✅ Route to reject a comment (restricted to ROLE_ADMIN)
    #[Route('/{id}/reject', name: 'api_comment_reject', methods: ['POST'])]
    public function rejectComment(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied: Only admins can reject comments.'], Response::HTTP_FORBIDDEN);
        }

        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        $comment->setApproved(false);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Comment rejected successfully', 'comment_id' => $comment->getId()]);
    }
}
