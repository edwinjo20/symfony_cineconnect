<?php
namespace App\Service;

use App\Entity\Review;
use App\Entity\Film;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ReviewService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Get all reviews from the database
     */
    public function getAllReviews(): array
    {
        return $this->entityManager->getRepository(Review::class)->findAll();
    }

    /**
     * Find a review by its ID
     */
    public function findReviewById(int $reviewId): ?Review
    {
        return $this->entityManager->getRepository(Review::class)->find($reviewId);
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
     * Update an existing review
     */
    public function updateReview(Review $review): void
    {
        $this->entityManager->flush();
    }

    /**
     * Delete a review
     */
    public function deleteReview(Review $review): void
    {
        $this->entityManager->remove($review);
        $this->entityManager->flush();
    }
}
