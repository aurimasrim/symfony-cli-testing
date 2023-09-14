<?php

namespace App\Testing;

use Certificationy\Answer;
use Certificationy\Collections\Answers;
use Certificationy\Collections\Questions;
use Certificationy\Question;

class QuestionsProvider
{
    private array $weightedData = [];
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

            foreach ($item['answers'] as $dataAnswer) {
                $answers->addAnswer(new Answer($dataAnswer['value'], $dataAnswer['correct']));
            }

            if (!isset($item['shuffle']) || true === $item['shuffle']) {
                $answers->shuffle();
            }

            $help = isset($item['help']) ? $item['help'] : null;

            $questions->add($key, new Question($item['question'], $item['category'], $answers, $help));
        }

        return $questions;
    }

    private function initializeWeightedData() : void
    {
        $this->weightedData = [];
        foreach ($this->data as $key => $item) {
            $answered = $item['answered'] ?? 0;
            $answeredCorrectly = $item['answered_correctly'] ?? 0;
            if ($answered === 0) {
                $this->weightedData[$key] = 1;
            } elseif ($answeredCorrectly === 0 && $answered >= 3) {
                $this->weightedData[$key] = 10000;
            }
            else {
                $this->weightedData[$key] = ($answered + 1) * 100 / ($answeredCorrectly + 1);
            }
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
            foreach ($item['answers'] as $dataAnswer) {
                $answers->addAnswer(new Answer($dataAnswer['value'], $dataAnswer['correct']));
            }
            if (!isset($item['shuffle']) || true === $item['shuffle']) {
                $answers->shuffle();
            }
            $help = isset($item['help']) ? $item['help'] : null;
            $questions->add($key, new Question($item['question'], $item['category'], $answers, $help));
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
