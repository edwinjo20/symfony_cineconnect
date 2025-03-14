<?php
namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\Film;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FilmControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private $film;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Create test data: User and Film
        $this->user = $this->createTestUser();
        $this->film = $this->createTestFilm();
    }

    // Test the Comment Creation in FilmController
    public function testCreateComment()
    {
        $this->client->loginUser($this->user);

        // POST request to create a comment for a film
        $this->client->request('POST', '/film/' . $this->film->getId(), [
            'review_id' => 1,  // Example review ID, ensure this is valid
            'content' => 'Amazing film!',
        ]);

        // Assert the response is successful (200 OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Verify comment was created in the database
        $comment = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'Amazing film!']);
        $this->assertNotNull($comment);
        $this->assertEquals('Amazing film!', $comment->getContent());
    }

    // Helper method to create a test user
    private function createTestUser(): User
    {
        // Remove existing user if already present
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }
    
        // Now create a new user
        $user = new User();
        $user->setUsername('testuser')
             ->setEmail('testuser@example.com')
             ->setPassword('password123');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    // Helper method to create a test film
    private function createTestFilm(): Film
    {
        $film = new Film();
        $film->setTitle('Test Film');
        $film->setDescription('A test film description');
        $this->entityManager->persist($film);
        $this->entityManager->flush();
        return $film;
    }
}
