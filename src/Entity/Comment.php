<?php
namespace App\Entity;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['comment:read'])]

    private $id;

    #[ORM\Column(type: 'text')]
    #[Groups(['comment:read'])]

    private $content;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['comment:read'])]
    private $date;

    #[ORM\ManyToOne(targetEntity: Review::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read'])]
    private $review;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read'])]

    private $user;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['comment:read'])]
    private $approved = false;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getFilm(): ?Film
    {
        return $this->review->getFilm();
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): self
    {
        $this->review = $review;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;
        return $this;
    }
}
