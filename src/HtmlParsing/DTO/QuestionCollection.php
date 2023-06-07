<?php

declare(strict_types=1);

namespace App\HtmlParsing\DTO;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ArrayCollection<int, Question>
 */
class QuestionCollection extends ArrayCollection
{
    public function merge(self $collection): self
    {
        foreach ($collection as $question) {
            $this->add($question);
        }

        return $this;
    }

    public function mergeUnique(self $collection, bool $override = false): self
    {
        foreach ($collection as $question) {
            $this->addIfNotExists($question);
        }

        return $this;
    }

    private function addIfNotExists(Question $question): void
    {
        foreach ($this as $existingQuestion) {
            if (
                $existingQuestion->getQuestion() === $question->getQuestion()
                && $existingQuestion->getCategory() === $question->getCategory()
            ) {
                return;
            }
        }

        $this->add($question);
    }
}
