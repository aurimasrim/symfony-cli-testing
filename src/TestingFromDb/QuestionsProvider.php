<?php

namespace App\TestingFromDb;

use Certificationy\Answer;
use Certificationy\Collections\Answers;
use Certificationy\Collections\Questions;
use App\Entity\Question as QuestionEntity;
use App\DTO\Question;

class QuestionsProvider
{
    private array $weightedData = [];

    /**
     * @param QuestionEntity[] $data
     */
    public function __construct(private array $data)
    {
    }

    public function get(int $nbQuestions, bool $onlyUnanswered = false) : Questions
    {
        $dataMax = count($this->data);
        $nbQuestions = min($nbQuestions, $dataMax);

        $questions = new Questions();

        for ($i = 0; $i < $nbQuestions; $i++) {
            $key = $onlyUnanswered ? $i : array_rand($this->data);
            $item = $this->data[$key];
            unset($this->data[$key]);

            $answers = new Answers();

            foreach ($item->getAnswers() as $dataAnswer) {
                $answers->addAnswer(new Answer($dataAnswer->getValue(), $dataAnswer->isCorrect()));
            }

            $help = $item->getHelp();

            $questions->add($key, new Question($item->getValue(), $item->getCategory()->getName(), $answers, $help, $item));
        }

        return $questions;
    }

    private function initializeWeightedData() : void
    {
        $this->weightedData = [];
        $maxTotal = \max(
            \array_map(
                fn (QuestionEntity $question): int => $question->getTotalCount(),
                $this->data
            ),
        );
        foreach ($this->data as $key => $item) {
            $answered = $item->getTotalCount();
            $answeredCorrectly = $item->getCorrectCount();
            $this->weightedData[$key] = QuestionWeightCalculator::calculate($answeredCorrectly, $answered, $maxTotal);
        }
    }

    public function getWeighted(int $number): Questions
    {
        $this->initializeWeightedData();
        $questions = new Questions();
        for ($i = 0; $i < $number; $i++) {
            $key = $this->getRandomWeightedElement($this->weightedData);
            $item = $this->data[$key];
            unset($this->weightedData[$key]);
            $answers = new Answers();
            foreach ($item->getAnswers() as $dataAnswer) {
                
                $answers->addAnswer(new Answer($dataAnswer->getValue(), $dataAnswer->isCorrect()));
            }
            
            $answers->shuffle();
            $help = $item->getHelp();
            $questions->add($key, new Question($item->getValue(), $item->getCategory()->getName(), $answers, $help, $item));
        }

        return $questions;
    }

    private function getRandomWeightedElement(array $weightedData)
    {
        $rand = mt_rand(1, (int) array_sum($weightedData));
        foreach ($weightedData as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }
    }
}
