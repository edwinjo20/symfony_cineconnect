<?php
namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationControllerTest extends WebTestCase
{
    public function testUserRegistration()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
    
        $csrfToken = $crawler->filter('input[name="registration_form[_token]"]')->attr('value');
    
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'testuser@example.com',
            'registration_form[username]' => 'testuser',
            'registration_form[plainPassword]' => 'TestPassword123',
            'registration_form[agreeTerms]' => '1',  
            'registration_form[_token]' => $csrfToken, 
        ]);
    
        $client->submit($form);
        
        dump('Response status: ' . $client->getResponse()->getStatusCode());
        $this->assertResponseRedirects('/login');
    
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'testuser@example.com']);
    
        if (!$user) {
            dump('User not found in database after registration.');
        } else {
            dump('User successfully registered! ID: ' . $user->getId());
        }
    
        $this->assertNotNull($user, 'User should be registered.');
    }
    
    
}
