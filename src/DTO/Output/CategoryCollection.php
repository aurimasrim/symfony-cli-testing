<?php

declare(strict_types=1);

namespace App\DTO\Output;

class CategoryCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param Category[] $categories
     */
    public function __construct(
        private array $categories = [],
    ) {
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        $this->categories[] = $category;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->categories);
    }

    public function count(): int
    {
        return count($this->categories);
    }

    public function countQuestions(): int
    {
        $count = 0;
        foreach ($this->categories as $category) {
            $count += \count($category->getQuestions());
        }

        return $count;
    }
}
