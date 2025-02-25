<?php

namespace App\Tests\Controller;

use App\Entity\Favorites;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FavoritesControllerTest extends WebTestCase{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $favoriteRepository;
    private string $path = '/favorites/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->favoriteRepository = $this->manager->getRepository(Favorites::class);

        foreach ($this->favoriteRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Favorite index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'favorite[user]' => 'Testing',
            'favorite[film]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->favoriteRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Favorites();
        $user = new \App\Entity\User();
        $user->setUsername('My Title');
        $fixture->setUser($user);

        $film = new \App\Entity\Film();
        $film->setTitle('My Title');
        $fixture->setFilm($film);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Favorite');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Favorites();
        $user = new \App\Entity\User();
        $user->setUsername('Value');
        $fixture->setUser($user);

        $film = new \App\Entity\Film();
        $film->setTitle('Value');
        $fixture->setFilm($film);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'favorite[user]' => 'Something New',
            'favorite[film]' => 'Something New',
        ]);

        self::assertResponseRedirects('/favorites/');

        $fixture = $this->favoriteRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getUser()->getUsername());
        self::assertSame('Something New', $fixture[0]->getFilm()->getTitle());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Favorites();
        $user = new \App\Entity\User();
        $user->setUsername('Value');
        $fixture->setUser($user);

        $film = new \App\Entity\Film();
        $film->setTitle('Value');
        $fixture->setFilm($film);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/favorites/');
        self::assertSame(0, $this->favoriteRepository->count([]));
    }
}
