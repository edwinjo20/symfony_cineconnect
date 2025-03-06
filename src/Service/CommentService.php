<?php
namespace App\Service;

use App\Entity\Comment;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CommentService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Get all comments
     */
    public function getAllComments(): array
    {
        return $this->entityManager->getRepository(Comment::class)->findAll();
    }

    /**
     * Handle comment submission
     */
    public function handleCommentSubmission(string $content, int $reviewId): ?string
    {
        $content = trim($content);
        if (empty($content)) {
            return 'Comment cannot be empty.';
        }

        $review = $this->entityManager->getRepository(Review::class)->find($reviewId);
        if (!$review) {
            return 'Review not found.';
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($this->security->getUser());
        $comment->setDate(new \DateTime());
        $comment->setReview($review);
        $comment->setApproved(false); // Requires admin approval

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return null; // No error
    }

    /**
     * Approve a comment
     */
    public function approveComment(int $commentId): ?string
    {
        $comment = $this->entityManager->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            return 'Comment not found';
        }

        $comment->setApproved(true);
        $this->entityManager->flush();

        return null; // No error
    }

    /**
     * Reject a comment
     */
    public function rejectComment(int $commentId): ?string
    {
        $comment = $this->entityManager->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            return 'Comment not found';
        }

        $comment->setApproved(false);
        $this->entityManager->flush();

        return null; // No error
    }
}
