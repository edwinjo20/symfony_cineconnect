<?php
namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Film;
use App\Entity\Review;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Genre;

class CommentControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function createAdminUser(): User
    {
        // Check if the admin already exists and reuse it if found
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        if ($existingAdmin) {
            return $existingAdmin;
        }

        // Create a new admin user
        $adminUser = new User();
        $adminUser->setUsername('admin')
            ->setEmail('admin@example.com')
            ->setPassword('adminpass')  // Ensure to hash the password before storing
            ->setRoles(['ROLE_ADMIN']);

        // Persist and flush the new user
        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();  // Ensure the new user is saved

        return $adminUser;
    }

    public function createTestFilm(): Film
    {
        // Check if a film already exists
        $existingFilm = $this->entityManager->getRepository(Film::class)->findOneBy(['title' => 'Test Film']);
        if ($existingFilm) {
            return $existingFilm;
        }

        // Create a new film
        $film = new Film();
        $film->setTitle('Test Film')
            ->setDescription('Test Description')
            ->setReleaseDate(new \DateTime())
            ->setGenre($this->createTestGenre());

        $this->entityManager->persist($film);
        $this->entityManager->flush();

        return $film;
    }

    public function createTestReview(): Review
    {
        // Check if a review already exists
        $existingReview = $this->entityManager->getRepository(Review::class)->findOneBy(['content' => 'Test Review']);
        if ($existingReview) {
            return $existingReview;
        }

        // Create a new review
        $review = new Review();
        $review->setContent('Test Review')
            ->setRatingGiven(5)
            ->setUser($this->createAdminUser())
            ->setFilm($this->createTestFilm());

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function createTestComment(int $reviewId): Comment
    {
        // Check if the comment already exists
        $existingComment = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'This is a comment']);
        if ($existingComment) {
            return $existingComment;
        }

        // Create a new comment
        $comment = new Comment();
        $comment->setContent('This is a comment')
            ->setDate(new \DateTime())
            ->setReview($this->entityManager->getRepository(Review::class)->find($reviewId))
            ->setUser($this->createAdminUser())
            ->setApproved(false);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function createTestGenre(): Genre
    {
        // Check if the genre already exists
        $existingGenre = $this->entityManager->getRepository(Genre::class)->findOneBy(['name' => 'Test Genre']);
        if ($existingGenre) {
            return $existingGenre; // If it exists, return the existing genre
        }

        // Otherwise, create a new one
        $genre = new Genre();
        $genre->setName('Test Genre');
        
        $this->entityManager->persist($genre);
        $this->entityManager->flush();
    
        return $genre;
    }
    
    public function testApproveComment()
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $film = $this->createTestFilm();
        $review = $this->createTestReview();
        $comment = $this->createTestComment($review->getId());

        $this->client->request('POST', '/api/comments/' . $comment->getId() . '/approve');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($comment);
        $this->assertTrue($comment->getApproved());
    }

    public function testRejectComment()
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        $film = $this->createTestFilm();
        $review = $this->createTestReview();
        $comment = $this->createTestComment($review->getId());

        $this->client->request('POST', '/api/comments/' . $comment->getId() . '/reject');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($comment);
        $this->assertFalse($comment->getApproved());
    }
}
