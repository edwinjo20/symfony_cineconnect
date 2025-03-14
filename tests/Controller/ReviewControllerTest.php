<?php
namespace App\Tests\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Film;
use App\Entity\Genre;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ReviewControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class); // Corrected line
    }

    public function testCreateReview()
    {
        // Create a user and log them in
        $user = $this->createTestUser();
        $film = $this->createTestFilm();
        
        // Add test genre if not exists
        $genre = $this->createTestGenre(); 
    
        // Log in as the test user
        $this->client->loginUser($user);
    
        // Request the 'new review' page
        $crawler = $this->client->request('GET', "/review/new/{$film->getId()}");
    
        // Ensure the page is loaded and contains the form
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    
        // Get the CSRF token (if required)
        $token = $crawler->filter('input[name="review[_token]"]')->attr('value');
    
        // Submit the form
        $form = $crawler->selectButton('Save')->form([
            'review[content]' => 'Amazing movie!',
            'review[ratingGiven]' => 5,
            'review[_token]' => $token, // Add CSRF token
        ]);
    
        // Submit the form
        $this->client->submit($form);
    
        // Assert that we are redirected to the correct film page
        $this->assertResponseRedirects("/film/{$film->getId()}");
    
        // Verify in the database that the review has been created
        $review = $this->entityManager->getRepository(Review::class)->findOneBy(['content' => 'Amazing movie!']);
        $this->assertNotNull($review);
        $this->assertEquals(5, $review->getRatingGiven());
    }
    
    private function createTestUser(): User
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }
    
        $user = new User();
        $user->setUsername('testuser')
             ->setEmail('testuser@example.com')
             ->setPassword('password123');
    
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        return $user;
    }
    

    private function createTestFilm(): Film
    {
        $film = new Film();
        $film->setTitle('Test Film')
            ->setDescription('A test movie description')
            ->setReleaseDate(new \DateTime())
            ->setGenre($this->createTestGenre());

        $this->entityManager->persist($film);
        $this->entityManager->flush();

        return $film;
    }

    private function createTestGenre(): Genre
    {
        $genreName = 'Action';
    
        // Check if the genre already exists in the database
        $genre = $this->entityManager->getRepository(Genre::class)->findOneBy(['name' => $genreName]);
    
        if (!$genre) {
            $genre = new Genre();
            $genre->setName($genreName);
            $this->entityManager->persist($genre);
            $this->entityManager->flush();
        }
    
        return $genre;
    }
}
