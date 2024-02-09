<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $value;

    #[ORM\Column(type: 'text')]
    private string $help;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Answer::class)]
    private Collection $answers;

    #[ORM\Column]
    private int $correctCount = 0;

    #[ORM\Column]
    private int $totalCount = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $excluded = false;

    public function __construct(string $value, Category $category, string $help)
    {
        $this->value = $value;
        $this->category = $category;
        $this->help = $help;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return Collection<Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function getCorrectCount(): int
    {
        return $this->correctCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function incrementCorrectCount(): void
    {
        $this->correctCount++;     
    }

    public function incrementTotalCount(): void
    {
        $this->totalCount++;
    }
}
