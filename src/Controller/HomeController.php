<?php

namespace App\Controller;

use App\Entity\Film;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $latestFilms = $entityManager->getRepository(Film::class)
            ->findBy([], ['releaseDate' => 'DESC'], 8); // Fetch only the latest 4 films

        return $this->render('home.html.twig', [
            'latestFilms' => $latestFilms,
        ]);
    }
}
