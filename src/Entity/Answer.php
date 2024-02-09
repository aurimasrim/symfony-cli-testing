<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private Question $question;

    #[ORM\Column(type: 'text')]
    private string $value;

    #[ORM\Column()]
    private bool $correct;

    public function __construct(Question $question, string $value, bool $correct)
    {
        $this->question = $question;
        $this->value = $value;
        $this->correct = $correct;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;     
    }

    public function isCorrect(): bool
    {
        return $this->correct;
    }
}
