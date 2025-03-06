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
        $this->security = $security; // ✅ FIX: Initialize $security
    }

    /**
     * Get all reviews from the database
     */
    public function getAllReviews(): array
    {
        return $this->entityManager->getRepository(Review::class)->findAll();
    }

    /**
     * Handle review submission
     */
    public function handleReviewSubmission(Review $review, Film $film): void
    {
        $review->setUser($this->security->getUser()); // ✅ FIX: $this->security now exists
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
