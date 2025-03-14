<?php
namespace App\Tests\Entity;

use App\Entity\Film;
use App\Entity\Review;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class FilmTest extends TestCase
{
    public function testAverageRating()
    {
        $film = new Film();
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('testuser@example.com');
        $user->setPassword('password123');

        // Creating reviews
        $review1 = new Review();
        $review1->setContent('Great movie!')
                ->setRatingGiven(5)
                ->setFilm($film)
                ->setUser($user);
        $film->addReview($review1);

        $review2 = new Review();
        $review2->setContent('Not bad')
                ->setRatingGiven(3)
                ->setFilm($film)
                ->setUser($user);
        $film->addReview($review2);

        // Test average rating calculation
        $this->assertEquals(4.0, $film->getAverageRating());
    }
}
