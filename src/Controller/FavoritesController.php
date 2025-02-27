<?php

namespace App\Controller;

use App\Entity\Favorites;
use App\Entity\Film;
use App\Form\FavoritesType;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/favorites')]
final class FavoritesController extends AbstractController{
    #[Route(name: 'app_favorites_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $favorites = $entityManager
            ->getRepository(Favorites::class)
            ->findAll();

        return $this->render('favorites/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }
    #[Route('/favorites/new', name: 'app_favorites_new', methods: ['POST'])]
    public function addFavorite(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $filmId = $request->request->get('filmId'); // Fetch the filmId from the POST request
    
        // Ensure the film exists before adding to favorites
        $film = $entityManager->getRepository(Film::class)->find($filmId);
    
        if (!$film) {
            $this->addFlash('error', 'Film not found');
            return $this->redirectToRoute('app_film_index');
        }
    
        // Check if the movie is already in the user's favorites
        $existingFavorite = $entityManager->getRepository(Favorites::class)->findOneBy([
            'user' => $user,
            'film' => $film
        ]);
    
        if ($existingFavorite) {
            $this->addFlash('info', 'This film is already in your favorites!');
            return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
        }
    
        // Create the Favorites entity
        $favorite = new Favorites();
        $favorite->setUser($user);
        $favorite->setFilm($film);
    
        // Persist the favorite to the database
        $entityManager->persist($favorite);
        $entityManager->flush();
    
        $this->addFlash('success', 'Film added to favorites!');
        return $this->redirectToRoute('app_film_show', ['id' => $film->getId()]);
    }
    
    #[Route('/{id}', name: 'app_favorites_show', methods: ['GET'])]
    public function show(Favorites $favorite): Response
    {
        return $this->render('favorites/show.html.twig', [
            'favorite' => $favorite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_favorites_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Favorites $favorite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FavoritesType::class, $favorite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_favorites_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('favorites/edit.html.twig', [
            'favorite' => $favorite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_favorites_delete', methods: ['POST'])]
    public function delete(Request $request, Favorites $favorite, EntityManagerInterface $entityManager): Response
    {
        // CSRF token validation
        if ($this->isCsrfTokenValid('delete'.$favorite->getId(), $request->request->get('_token'))) {
            $entityManager->remove($favorite);
            $entityManager->flush();
            
            $this->addFlash('success', 'Favorite removed successfully.');
        }
    
        return $this->redirectToRoute('app_profile'); // Redirect back to profile page
    }
}    
