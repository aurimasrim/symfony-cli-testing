<?php

declare(strict_types=1);

namespace App\HtmlParsing\DTO\Output;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Question
{
    /**
     * @param Answer[] $answers
     */
    public function __construct(
        private readonly string $question,
        private readonly array $answers,
        private readonly string $help,
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

    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @SerializedName("has_correct_answers")
     */
    public function hasCorrectAnswers(): bool
    {
        return count(\array_filter($this->answers, static fn (Answer $answer): bool => $answer->isCorrect())) > 0;
    }
}
