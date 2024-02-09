<?php

namespace App\Tests\TestingFromDb;

use App\TestingFromDb\QuestionWeightCalculator;
use PHPUnit\Framework\TestCase;

class QuestionWeightCalculatorTest extends TestCase
{
    /**
     * @dataProvider calculates2DataProvider
     */
    public static function testCalculates(int $answeredCorrectly, int $answered, int $expectedWeight): void
    {
        self::assertSame(
            $expectedWeight,
            QuestionWeightCalculator::calculate($answeredCorrectly, $answered, 6),
        );
    }

    public function calculatesDataProvider(): iterable
    {
        yield [0, 0, 300];
        yield [0, 1, 150];
        yield [0, 2, 150];
        yield [0, 3, 150];
        yield [1, 1, 33];
        yield [1, 2, 50];
        yield [1, 3, 50];
        yield [1, 4, 50];
        yield [2, 2, 33];
        yield [2, 3, 50];
        yield [2, 4, 50];
        yield [3, 3, 33];
        yield [3, 4, 50];
        yield [3, 5, 50];
        yield [4, 4, 10];
        yield [4, 5, 10];
        yield [4, 6, 50];
    }

    public function calculates2DataProvider(): iterable
    {
        yield [0, 0, 3000];
        yield [0, 1, 1500];
        yield [0, 2, 3000];
        yield [0, 3, 4500];
        yield [1, 1, 187];
        yield [1, 2, 250];
        yield [1, 3, 312];
        yield [1, 4, 375];
        yield [2, 2, 166];
        yield [2, 3, 208];
        yield [2, 4, 250];
        yield [3, 3, 156];
        yield [3, 4, 187];
        yield [3, 5, 218];
        yield [4, 4, 150];
        yield [4, 5, 175];
        yield [4, 6, 200];
        yield [5, 5, 145];
        yield [5, 6, 166];
    }
}
