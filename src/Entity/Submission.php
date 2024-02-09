<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $correctCount = 0;

    #[ORM\Column]
    private int $totalCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $correctCount, int $totalCount)
    {
        $this->correctCount = $correctCount;
        $this->totalCount = $totalCount;
        $this->createdAt = new \DateTimeImmutable();
    }
}
