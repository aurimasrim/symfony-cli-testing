<?php

declare(strict_types=1);

namespace App\DTO\Output;

class Answer
{
    public function __construct(
        private readonly string $value,
        private readonly ?bool $correct,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isCorrect(): ?bool
    {
        return $this->correct;
    }
}
