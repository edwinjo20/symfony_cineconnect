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
    // View all films
    #[Route('/', name: 'app_film_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $films = $entityManager->getRepository(Film::class)->findAll();
        return $this->render('film/index.html.twig', ['films' => $films]);
    }

    // Show film details (and allow reviews/comments)
    #[Route('/{id}', name: 'app_film_show', methods: ['GET', 'POST'])]
    
    public function show(Film $film, Request $request, EntityManagerInterface $entityManager): Response
        {
            // Handle comment forms for each review
            $commentForms = [];
            foreach ($film->getReviews() as $review) {
                $comment = new Comment();
                $commentForm = $this->createForm(CommentType::class, $comment);
                $commentForm->handleRequest($request);
        
                // Check if the form is submitted and valid
                if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                    
                    // Ensure the user is logged in before saving the comment
                    if (!$this->getUser()) {
                        return $this->redirectToRoute('app_login'); // Redirect to login if not logged in
                    }
        
                    // Set the user, date, and review before saving the comment
                    $comment->setUser($this->getUser());
                    $comment->setDate(new \DateTime());
                    $comment->setReview($review);
        
                    // Persist the comment and flush to the database
                    $entityManager->persist($comment);
                    $entityManager->flush();
        
                    // Redirect to the same page after saving the comment
                    return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
                }
        
                // Store the form for each review in the array
                $commentForms[$review->getId()] = $commentForm->createView();
            }
        
            // Handle new review form
            $reviewForm = $this->createForm(ReviewType::class, new Review());
            $reviewForm->handleRequest($request);
        
            // Handle review submission logic here...
            
            return $this->render('film/show.html.twig', [
                'film' => $film,
                'reviews' => $film->getReviews(),
                'commentForms' => $commentForms,
                'reviewForm' => $reviewForm->createView(),
            ]);
        }
        
    
}
