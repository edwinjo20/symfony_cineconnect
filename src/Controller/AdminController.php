<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Comment;
use App\Entity\Genre;
use App\Entity\User;
use App\Form\FilmType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    /**
     * 📌 Admin Dashboard - Displays all films, comments, and users
     */
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')] // Ensures only admins can access
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $films = $entityManager->getRepository(Film::class)->findAll();
        $comments = $entityManager->getRepository(Comment::class)->findBy(['approved' => false]);
        $genres = $entityManager->getRepository(Genre::class)->findAll();
        $users = $entityManager->getRepository(User::class)->findAll(); // Fetch users

        return $this->render('admin/dashboard.html.twig', [
            'films' => $films,
            'comments' => $comments,
            'genres' => $genres,
            'users' => $users, // Pass users to template
        ]);
    }

    /**
     * 🎬 Create a new film (Admin only)
     */
    #[Route('/film/new', name: 'admin_film_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newFilm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $film = new Film();
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $file = $form->get('imagePath')->getData();
            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('images_directory'), 
                    $newFilename
                );
                $film->setImagePath($newFilename);
            }

            $entityManager->persist($film);
            $entityManager->flush();

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/film/create_film.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * 📝 Edit an existing film (Admin only)
     */
    #[Route('/film/{id}/edit', name: 'admin_film_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editFilm(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file update
            $file = $form->get('imagePath')->getData();
            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $film->setImagePath($newFilename);
            }

            $entityManager->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('film/edit.html.twig', [
            'film' => $film,
            'form' => $form->createView(),
        ]);
    }

    /**
     * ❌ Delete a film (Admin only)
     */
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

    /**
     * ✅ Approve a comment (Admin only)
     */
    #[Route('/comment/{id}/approve', name: 'admin_comment_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setApproved(true);
        $entityManager->flush();
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * 🗑️ Delete a comment (Admin only)
     */
    #[Route('/comment/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($comment);
        $entityManager->flush();
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * 🔼 Promote a user to ADMIN
     */
    #[Route('/user/{id}/promote', name: 'admin_promote_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function promoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $this->addFlash('success', 'User promoted to Admin.');
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * 🚫 Block a user (Prevent login)
     */
    #[Route('/user/{id}/block', name: 'admin_block_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function blockUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBlocked(true);
        $entityManager->flush();

        $this->addFlash('warning', 'User has been blocked.');
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * ✅ Unblock a user
     */
    #[Route('/user/{id}/unblock', name: 'admin_unblock_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unblockUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBlocked(false);
        $entityManager->flush();

        $this->addFlash('success', 'User has been unblocked.');
        return $this->redirectToRoute('admin_dashboard');
    }
}
