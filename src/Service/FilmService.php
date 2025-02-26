<?php
namespace App\Service;

use App\Entity\Film;
use App\Entity\Review;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class FilmService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Get all films from the database
     */
    public function getAllFilms(): array
    {
        return $this->entityManager->getRepository(Film::class)->findAll();
    }

    /**
     * Handle review submission
     */
    public function handleReviewSubmission(Review $review, Film $film): void
    {
        $review->setUser($this->security->getUser());
        $review->setFilm($film);
        $review->setPublicationDate(new \DateTime());

        $this->entityManager->persist($review);
        $this->entityManager->flush();
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
        $comment->setApproved(false); // Admin approval needed

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return null; // No error
    }
}
