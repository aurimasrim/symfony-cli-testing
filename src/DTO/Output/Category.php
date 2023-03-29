<?php

declare(strict_types=1);

namespace App\DTO\Output;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Category
{
    /**
     * @param Question[] $questions
     */
    public function __construct(
        #[SerializedName('category')]
        private readonly string $name,
        private array $questions = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): void
    {
        $this->questions[] = $question;
    }
}
