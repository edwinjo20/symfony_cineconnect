<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Film;
use App\Form\ReviewType;
use App\Service\ReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/review')]
final class ReviewController extends AbstractController
{
    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    // ✅ List all reviews
    #[Route(name: 'app_review_index', methods: ['GET'])]
    public function index(): Response
    {
        $reviews = $this->reviewService->getAllReviews();

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    // ✅ Create a new review
    #[Route('/new/{filmId}', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $filmId, EntityManagerInterface $entityManager): Response
    {
        $film = $entityManager->getRepository(Film::class)->find($filmId);
    
        if (!$film) {
            throw $this->createNotFoundException('Film not found.');
        }
    
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->reviewService->handleReviewSubmission($review, $film);
            return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
        }
    
        return $this->render('review/new.html.twig', [
            'review' => $review,
            'form' => $form->createView(),
        ]);
    }

    // ✅ Show a single review
    #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }

    // ✅ Edit an existing review
    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review): Response
    {
        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to edit this review.');
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->reviewService->updateReview($review);

            return $this->redirectToRoute('app_film_show', ['id' => $review->getFilm()->getId()]);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form->createView(),
        ]);
    }

    // ✅ Delete a review
    #[Route('/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review): Response
    {
        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to delete this review.');
        }
    
        if ($this->isCsrfTokenValid('delete' . $review->getId(), $request->request->get('_token'))) {
            $this->reviewService->deleteReview($review);
        }
    
        return $this->redirectToRoute('app_film_show', ['id' => $review->getFilm()->getId()]);
    }
    
}
