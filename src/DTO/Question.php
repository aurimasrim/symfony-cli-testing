<?php

declare(strict_types=1);

namespace App\DTO;

class Question
{
    /**
     * @param string[] $answers
     * @param string[] $correctAnswers
     */
    public function __construct(
        private readonly string $question,
        private readonly array $answers,
        private readonly array $correctAnswers,
        private readonly string $help,
        private readonly string $category,
    ) {
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @return string[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @return string[]
     */
    public function getCorrectAnswers(): array
    {
        return $this->correctAnswers;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
