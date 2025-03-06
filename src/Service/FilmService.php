<?php
namespace App\Service;

use App\Entity\Film;
use Doctrine\ORM\EntityManagerInterface;

class FilmService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get all films from the database
     */
    public function getAllFilms(): array
    {
        return $this->entityManager->getRepository(Film::class)->findAll();
    }

    /**
     * Get films filtered by genre
     */
    public function getFilmsByGenre(?int $genreId): array
    {
        if (!$genreId) {
            return $this->getAllFilms(); // If no genre, return all films
        }

        return $this->entityManager->getRepository(Film::class)->createQueryBuilder('f')
            ->where('f.genre = :genreId')
            ->setParameter('genreId', $genreId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search films by title
     */
    public function searchFilms(string $query): array
    {
        $query = trim($query);
        if (empty($query)) {
            return $this->getAllFilms(); // Return all films if no search query
        }

        return $this->entityManager->getRepository(Film::class)->createQueryBuilder('f')
            ->where('LOWER(f.title) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
