<?php

declare(strict_types=1);

namespace App\DTO\Output;

class UniqueQuestionCollection implements \IteratorAggregate
{
    /**
     * @param Question[] $questions
     */
    public function __construct(
        private array $questions = [],
    ) {
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
        foreach ($this->questions as $i => $existingQuestion) {
            if ($existingQuestion->getQuestion() === $question->getQuestion()) {
                if (!$existingQuestion->hasCorrectAnswers() && $question->hasCorrectAnswers()) {
                    $this->questions[$i] = $question;

                }

                return;
            }
        }

        $this->questions[] = $question;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->questions);
    }
}
