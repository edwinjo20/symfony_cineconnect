<?php
namespace App\Tests\Entity;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Film;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    public function testReviewEntity()
    {
        $user = new User();
        $user->setUsername('testuser');
        
        $film = new Film();
        $film->setTitle('Test Film');

        $review = new Review();
        $review->setUser($user);
        $review->setFilm($film);
        $review->setContent('Great movie!');
        $review->setRatingGiven(5);
        
        $this->assertEquals('Great movie!', $review->getContent());
        $this->assertEquals(5, $review->getRatingGiven());
        $this->assertEquals('testuser', $review->getUser()->getUsername());
        $this->assertEquals('Test Film', $review->getFilm()->getTitle());
    }
}
