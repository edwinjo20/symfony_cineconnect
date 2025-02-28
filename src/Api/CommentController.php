<?php
namespace App\Api;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Film;  // Ensure Film entity is included
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/comments')]
final class CommentController extends AbstractController
{
// Route to list all comments (accessible for users with role ROLE_ADMIN)
#[Route('', name: 'api_comment_index', methods: ['GET'])]
public function index(EntityManagerInterface $entityManager): JsonResponse
{
    $comments = $entityManager
        ->getRepository(Comment::class)
        ->findAll();

    $commentData = [];

    foreach ($comments as $comment) {
        $film = $comment->getFilm();  // Get the film associated with the comment

        $commentData[] = [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'date' => $comment->getDate()->format('Y-m-d H:i:s'),
            'approved' => $comment->getApproved(),
            'user' => $comment->getUser()->getUsername(),
            'film' => [
                'title' => $film ? $film->getTitle() : 'Unknown Film',
                'releaseDate' => $film ? $film->getReleaseDate()->format('Y-m-d') : 'Unknown Release Date',
                // Add other film details as needed
            ],
        ];
    }

    return new JsonResponse($commentData);
}


    // Route to approve or reject a comment
    #[Route('/{id}/approve', name: 'api_comment_approve', methods: ['POST'])]
    public function approveComment(int $id, EntityManagerInterface $entityManager, UserInterface $user): JsonResponse
    {
        // Ensure the user is an admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied: Only admins can approve or reject comments.'], Response::HTTP_FORBIDDEN);
        }

        // Find the comment by ID
        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Approve the comment
        $comment->setApproved(true);

        // Persist and flush the changes
        $entityManager->flush();

        return new JsonResponse(['message' => 'Comment approved successfully', 'comment_id' => $comment->getId()]);
    }

    // Route to reject a comment
    #[Route('/{id}/reject', name: 'api_comment_reject', methods: ['POST'])]
    public function rejectComment(int $id, EntityManagerInterface $entityManager, UserInterface $user): JsonResponse
    {
        // Ensure the user is an admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied: Only admins can approve or reject comments.'], Response::HTTP_FORBIDDEN);
        }

        // Find the comment by ID
        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Reject the comment (set approved to false)
        $comment->setApproved(false);

        // Persist and flush the changes
        $entityManager->flush();

        return new JsonResponse(['message' => 'Comment rejected successfully', 'comment_id' => $comment->getId()]);
    }
}
