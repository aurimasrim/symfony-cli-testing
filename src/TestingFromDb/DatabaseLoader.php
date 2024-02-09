<?php

namespace App\TestingFromDb;

use App\Repository\QuestionRepository;
use Certificationy\Collections\Questions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class DatabaseLoader
{
    /**
     * @var Questions
     */
    private $questions;

    public function __construct(private readonly QuestionRepository $questionRepository)
    {
    }

    /**
     * @inheritdoc
     */
    public function load(int $nbQuestions, array $categories, bool $onlyUnanswered = false) : QuestionsProvider
    {
        return new QuestionsProvider($this->getQuestions($categories, $onlyUnanswered));
    }

    /**
     * @inheritdoc
     *
     * @throws \ErrorException
     */
    public function all() : Questions
    {
        if (is_null($this->questions)) {
            $this->questions = $this->load(PHP_INT_MAX, [])->get(PHP_INT_MAX);
        }

        return $this->questions;
    }

    /**
     * @inheritdoc
     */
    public function categories(bool $onlyUnanswered = false) : array
    {
        $categories = [];
        $files = $this->prepareFromYaml([], $onlyUnanswered);

        foreach ($files as $file) {
            $categories[] = $file['category'];
        }

        return array_unique($categories);
    }

    /**
     * @param array $categories : List of categories which should be included, empty array = all
     *
     * @return Question[]
     */
    protected function getQuestions(array $categories = [], bool $onlyUnanswered = false) : array
    {
        return $this->questionRepository->findBy(['excluded' => false]);
    }
}
