<?php

declare(strict_types=1);

namespace App\Aggregator;

use App\DTO\Output\Answer;
use App\DTO\Output\Category;
use App\DTO\Output\CategoryCollection;
use App\DTO\Output\Question;
use App\DTO\Question as InputQuestion;

class QuestionToCategoriesAggregator
{
    /**
     * @param InputQuestion[] $questions
     */
    public function aggregate(array $questions): CategoryCollection
    {
        $categoryCollection = new CategoryCollection();
        foreach ($questions as $question) {
            $category = $this->getCategory($categoryCollection, $question);
            $category->addQuestion($this->getOutputQuestion($question));
        }

        return $categoryCollection;
    }

    private function getCategory(CategoryCollection $categoryCollection, InputQuestion $question): Category
    {
        $categoryName = $question->getCategory();
        foreach ($categoryCollection as $category) {
            if ($category->getName() === $categoryName) {
                return $category;
            }
        }

        $category = new Category($categoryName);
        $categoryCollection->addCategory($category);

        return $category;
    }

    private function getOutputQuestion(InputQuestion $question): Question
    {
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            $answers[] = new Answer($answer, in_array($answer, $question->getCorrectAnswers()));
        }

        return new Question(
            $question->getQuestion(),
            $answers,
            $question->getHelp(),
        );
    }
}
