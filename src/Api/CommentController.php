<?php

namespace App\Api;

use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/comments')]
final class CommentController extends AbstractController
{
    private CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    // ✅ Route to list all comments (restricted to ROLE_ADMIN)
    #[Route('', name: 'api_comment_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $comments = $this->commentService->getAllComments();
        return $this->json($comments, 200, [], ['groups' => 'comment:read']);
    }

    // ✅ Route to approve a comment (restricted to ROLE_ADMIN)
    #[Route('/{id}/approve', name: 'api_comment_approve', methods: ['POST'])]
    public function approveComment(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $error = $this->commentService->approveComment($id);
        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Comment approved successfully']);
    }

    // ✅ Route to reject a comment (restricted to ROLE_ADMIN)
    #[Route('/{id}/reject', name: 'api_comment_reject', methods: ['POST'])]
    public function rejectComment(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $error = $this->commentService->rejectComment($id);
        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Comment rejected successfully']);
    }
}
