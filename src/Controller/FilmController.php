<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Form\CommentType;
use App\Service\FilmService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/film')]
final class FilmController extends AbstractController
{
    private FilmService $filmService;

    public function __construct(FilmService $filmService)
    {
        $this->filmService = $filmService;
    }

    /**
     * 📌 Display all films
     */
    #[Route('/', name: 'app_film_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('film/index.html.twig', [
            'films' => $this->filmService->getAllFilms()
        ]);
    }

    /**
     * 📌 Show film details and allow reviews & comments
     */
    #[Route('/{id}', name: 'app_film_show', methods: ['GET', 'POST'])]
    public function show(Film $film, Request $request): Response
    {
        // ✅ Handle review form
        $reviewForm = $this->createForm(ReviewType::class, new Review());
        $reviewForm->handleRequest($request);

        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            $this->filmService->handleReviewSubmission($reviewForm->getData(), $film);
            return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
        }

        // ✅ Prepare comment forms
        $commentForms = [];
        foreach ($film->getReviews() as $review) {
            $commentForms[$review->getId()] = $this->createForm(CommentType::class)->createView();
        }

        // ✅ Handle comment submission
        if ($request->isMethod('POST') && $request->request->has('review_id')) {
            $error = $this->filmService->handleCommentSubmission(
                $request->request->get('content'),
                (int) $request->request->get('review_id')
            );

            if ($error) {
                $this->addFlash('error', $error);
            } else {
                $this->addFlash('success', 'Comment submitted for approval.');
            }
            
            return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
        }

        return $this->render('film/show.html.twig', [
            'film' => $film,
            'reviews' => $film->getReviews(),
            'commentForms' => $commentForms,
            'reviewForm' => $reviewForm->createView(),
        ]);
    }
}
