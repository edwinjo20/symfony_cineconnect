<?php 

namespace App\Tests\Controller;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Film;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewControllerTest extends WebTestCase {
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $reviewRepository;
    private string $path = '/review/';

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->reviewRepository = $this->manager->getRepository(Review::class);

        // Clean up database
        foreach ($this->reviewRepository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    public function testIndex(): void {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Review index');
    }

    public function testNew(): void {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'review[content]' => 'Testing',
            'review[ratingGiven]' => 5,
            'review[publicationDate]' => (new \DateTime())->format('Y-m-d'),
            'review[film]' => 1,
            'review[user]' => 1,
        ]);

        self::assertResponseRedirects($this->path);

        $reviews = $this->reviewRepository->findAll();
        self::assertCount(1, $reviews);
        self::assertInstanceOf(Review::class, $reviews[0]);
    }

    public function testShow(): void {
        $this->markTestIncomplete();

        // Create a new review
        $fixture = new Review();
        $fixture->setContent('My Title');
        $fixture->setRatingGiven(5);
        $fixture->setPublicationDate(new \DateTime('now'));
        $fixture->setFilm($this->manager->getRepository(Film::class)->find(1));
        $fixture->setUser($this->manager->getRepository(User::class)->find(1));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Review');
    }

    public function testEdit(): void {
        $this->markTestIncomplete();

        // Create and persist a review
        $fixture = new Review();
        $fixture->setContent('Value');
        $fixture->setRatingGiven(5);
        $fixture->setPublicationDate(new \DateTime('now'));
        $fixture->setFilm($this->manager->getRepository(Film::class)->find(1));
        $fixture->setUser($this->manager->getRepository(User::class)->find(1));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'review[content]' => 'Something New',
            'review[ratingGiven]' => 10,  // Assuming max rating is 10
            'review[publicationDate]' => (new \DateTime())->format('Y-m-d'),
            'review[film]' => 1,
            'review[user]' => 1,
        ]);

        self::assertResponseRedirects($this->path);

        // Retrieve updated entity
        $updatedFixture = $this->reviewRepository->findAll();
        self::assertNotEmpty($updatedFixture, 'No review found after edit');
        self::assertInstanceOf(Review::class, $updatedFixture[0]);
                
        // Assertions to validate modifications
        /** @var Review $review */
        $review = $updatedFixture[0];

        self::assertSame('Something New', $review->getContent());
        self::assertSame(10, $review->getRatingGiven());
        self::assertInstanceOf(\DateTime::class, $review->getPublicationDate());
        self::assertInstanceOf(Film::class, $review->getFilm());
        self::assertInstanceOf(User::class, $review->getUser());
        
    }

    public function testRemove(): void {
        $this->markTestIncomplete();

        // Create and persist a review
        $fixture = new Review();
        $fixture->setContent('Value');
        $fixture->setRatingGiven(5);
        $fixture->setPublicationDate(new \DateTime('now'));
        $fixture->setFilm($this->manager->getRepository(Film::class)->find(1));
        $fixture->setUser($this->manager->getRepository(User::class)->find(1));

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects($this->path);
        self::assertSame(0, $this->reviewRepository->count([]));
    }
}
