<?php

declare(strict_types=1);

namespace App\DTO\Output;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Category
{
    public function __construct(
        #[SerializedName('category')]
        private readonly string $name,
        private readonly UniqueQuestionCollection $questions = new UniqueQuestionCollection(),
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuestions(): UniqueQuestionCollection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): void
    {
        $this->questions->addQuestion($question);
    }
}
