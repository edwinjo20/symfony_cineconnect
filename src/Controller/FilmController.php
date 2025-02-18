<?php
namespace App\Controller;

use App\Entity\Film;
use App\Entity\Review;
use App\Entity\Comment;
use App\Form\ReviewType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/film')]
final class FilmController extends AbstractController
{
    // 📌 **View all films**
    #[Route('/', name: 'app_film_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $films = $entityManager->getRepository(Film::class)->findAll();
        return $this->render('film/index.html.twig', ['films' => $films]);
    }

    // 📌 **Show film details + allow reviews & comments**
    #[Route('/{id}', name: 'app_film_show', methods: ['GET', 'POST'])]
    public function show(Film $film, Request $request, EntityManagerInterface $entityManager): Response
    {
        // ✅ **Handle Review Form**
        $reviewForm = $this->createForm(ReviewType::class, new Review());
        $reviewForm->handleRequest($request);

        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            $review = $reviewForm->getData();
            $review->setUser($this->getUser());
            $review->setFilm($film);
            $review->setPublicationDate(new \DateTime());

            $entityManager->persist($review);
            $entityManager->flush();

            return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
        }

        // ✅ **Prepare Comment Forms for Each Review**
        $commentForms = [];
        foreach ($film->getReviews() as $review) {
            $commentForm = $this->createForm(CommentType::class);
            $commentForms[$review->getId()] = $commentForm->createView();
        }

        // ✅ **Handle Comment Submission**
        if ($request->isMethod('POST') && $request->request->has('review_id')) {
            $reviewId = $request->request->get('review_id');
            $review = $entityManager->getRepository(Review::class)->find($reviewId);

            if (!$review) {
                throw $this->createNotFoundException('Review not found.');
            }

            // 🔍 **Get comment content & validate**
            $content = trim($request->request->get('content'));

            if (empty($content)) {
                $this->addFlash('error', 'Comment cannot be empty.');
                return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
            }

            // ✅ **Save Comment**
            $comment = new Comment();
            $comment->setContent($content);
            $comment->setUser($this->getUser());
            $comment->setDate(new \DateTime());
            $comment->setReview($review);
            $comment->setApproved(false); // Admin approval needed

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment submitted for approval.');
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
