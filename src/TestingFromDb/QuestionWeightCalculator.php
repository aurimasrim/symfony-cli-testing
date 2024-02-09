<?php

namespace App\TestingFromDb;

class QuestionWeightCalculator
{
    private const MULTIPLIER = 1000;

    public static function calculate(int $answeredCorrectly, int $answered, int $maxAnswered): int
    {
        if ($answeredCorrectly > $answered) {
            throw new \InvalidArgumentException('Answered correctly cannot be higher than total answered');
        }

        if ($answered === 0) {
            return self::MULTIPLIER * 3;
        } elseif ($answeredCorrectly === 0) {
            return self::MULTIPLIER * 1.5 * $answered;
        // } elseif ($answeredCorrectly > 3 && ($answered - $answeredCorrectly) <= 1) {
            // return (int)(self::MULTIPLIER / 10);
        // } elseif ($answeredCorrectly < $answered) {
            // return (int)(self::MULTIPLIER / 2);
        } else {
            // return (int)(self::MULTIPLIER / 3);
                //                                  3       + 2 / 3 + 1 * 100 / 5
            return (int)(($answered + 2) ** 5 / ($answeredCorrectly + 1) ** 5 * self::MULTIPLIER / ($maxAnswered + 2));
                // $this->weightedData[$key] = ($answered + 1) * 100 / ($answeredCorrectly + 1);
        }
    }
}
