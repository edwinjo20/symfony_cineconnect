<?php
namespace App\Controller;

use App\Entity\Film;
use App\Entity\Comment;
use App\Form\FilmType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    // Admin dashboard (View all films and comments)
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $films = $entityManager->getRepository(Film::class)->findAll();
        $comments = $entityManager->getRepository(Comment::class)->findBy(['approved' => false]); // Unapproved comments

        return $this->render('admin/dashboard.html.twig', [
            'films' => $films,
            'comments' => $comments,
        ]);
    }

    // Create a new film (Admin only)
    #[Route('/film/new', name: 'admin_film_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newFilm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $film = new Film();
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imagePath')->getData();
            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('images_directory'), // Store in the `public/uploads/images` folder
                    $newFilename
                );
                $film->setImagePath($newFilename); // Set the image path
            }

            $entityManager->persist($film);
            $entityManager->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/film/create_film.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Edit an existing film (Admin only)
    #[Route('/film/{id}/edit', name: 'admin_film_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editFilm(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imagePath')->getData();
            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('images_directory'), // Store in the `public/uploads/images` folder
                    $newFilename
                );
                $film->setImagePath($newFilename); // Update the image path
            }

            $entityManager->flush(); // This will update the existing film
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('film/edit.html.twig', [
            'film' => $film,
            'form' => $form->createView(),
        ]);
    }

    // Delete a film (Admin only)
    #[Route('/film/{id}', name: 'admin_film_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteFilm(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $film->getId(), $request->request->get('_token'))) {
            $entityManager->remove($film);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_dashboard');
    }

    // Approve a comment (Admin only)
    #[Route('/comment/{id}/approve', name: 'admin_comment_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setApproved(true);
        $entityManager->flush();

        return $this->redirectToRoute('admin_dashboard');
    }

    // Delete a comment (Admin only)
    #[Route('/comment/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->redirectToRoute('admin_dashboard');
    }
}
