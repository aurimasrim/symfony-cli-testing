<?php

namespace App\Testing;

use Certificationy\Collections\Questions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class YamlLoader
{
    /**
     * @var Questions
     */
    private $questions;

    /**
     * @var string[]
     */
    private $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @inheritdoc
     */
    public function load(int $nbQuestions, array $categories, bool $onlyUnanswered = false) : QuestionsProvider
    {
        return new QuestionsProvider($this->prepareFromYaml($categories, $onlyUnanswered));
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
     * Prepares data from Yaml files and returns an array of questions
     *
     * @param array $categories : List of categories which should be included, empty array = all
     *
     * @return array
     */
    protected function prepareFromYaml(array $categories = [], bool $onlyUnanswered = false) : array
    {
        $data = array();

        foreach ($this->paths as $path) {
            $files = Finder::create()->files()->in($path)->name('*.yml')->name('*.yaml');

            foreach ($files as $file) {
                $fileData = Yaml::parse($file->getContents());
                $category = $fileData['category'];

                if (count($categories) > 0 && !in_array($category, $categories)) {
                    continue;
                }

                array_walk($fileData['questions'], function (&$item) use ($category) {
                    $item['category'] = $category;
                });

                $data = array_merge($data, $fileData['questions']);
                if ($onlyUnanswered) {
                    $data = array_filter($data, function ($item) {
                        return isset($item['has_correct_answers']) && $item['has_correct_answers'] === false;
                    });
                    $data = \array_values($data);
                } else {
                    $data = array_filter($data, function ($item) {
                        return isset($item['has_correct_answers']) && $item['has_correct_answers'] === true;
                    });
                    $data = \array_values($data);
                }
            }
        }

        return $data;
    }
}
