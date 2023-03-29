<?php

declare(strict_types=1);

namespace App\DTO\Output;

class Question
{
    /**
     * @param Answer[] $answers
     */
    public function __construct(
        private readonly string $question,
        private readonly array $answers,
    ) {
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @return Answer[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }
}
