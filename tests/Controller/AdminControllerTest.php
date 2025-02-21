<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Film;
use App\Entity\Comment;
use App\Entity\User;

final class AdminControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    
        // Fetch admin user from the database
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
    
        if (!$adminUser) {
            $this->markTestSkipped('No admin user found in database. Skipping test.');
        } else {
            $this->client->loginUser($adminUser);
        }
    }
    
    public function testIndex(): void 
    {
        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
    }

    public function testNewFilm(): void
    {
        $this->client->request('GET', '/admin/film/new');
        self::assertResponseRedirects('/login'); // Should redirect if not logged in

        // Login as admin
        // $this->client->loginUser($admin);
        
        $this->client->request('GET', '/admin/film/new');
        self::assertResponseIsSuccessful();
        
        // Test form submission
        $this->client->submitForm('Save', [
            'film[title]' => 'Test Film',
            'film[description]' => 'Test Description',
            'film[releaseDate]' => '2023-01-01',
            // Add other required fields
        ]);
        
        self::assertResponseRedirects('/admin');
    }

    public function testEditFilm(): void
    {
        $film = $this->entityManager->getRepository(Film::class)->findOneBy([]);
        
        if ($film) {
            $this->client->request('GET', '/admin/film/'.$film->getId().'/edit');
            self::assertResponseRedirects('/login'); // Should redirect if not logged in
        }
    }

    public function testDeleteFilm(): void
    {
        $film = $this->entityManager->getRepository(Film::class)->findOneBy([]);
        
        if ($film) {
            $this->client->request('POST', '/admin/film/'.$film->getId(), [
                'token' => 'invalid_token'
            ]);
            self::assertResponseRedirects('/login');
        }
    }

    public function testApproveComment(): void
    {
        $comment = $this->entityManager->getRepository(Comment::class)->findOneBy(['approved' => false]);
        
        if ($comment) {
            $this->client->request('POST', '/admin/comment/'.$comment->getId().'/approve');
            self::assertResponseRedirects('/login');
        }
    }

    public function testDeleteComment(): void
    {
        $comment = $this->entityManager->getRepository(Comment::class)->findOneBy([]);
        
        if ($comment) {
            $this->client->request('POST', '/admin/comment/'.$comment->getId().'/delete');
            self::assertResponseRedirects('/login');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
