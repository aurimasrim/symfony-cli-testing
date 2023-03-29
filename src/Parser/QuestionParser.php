<?php

declare(strict_types=1);

namespace App\Parser;

use App\DTO\Question;
use Symfony\Component\DomCrawler\Crawler;

class QuestionParser
{
    /**
     * @return Question[]
     */
    public function parse(string $html): array
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $questions = $crawler->filter('div.ui.card')
            ->each(
                function (Crawler $questionCard): ?Question {
                    $header = $questionCard->filter('.header')->text();
                    if (str_contains($header, 'Congratulation')) {
                        return null;
                    }
                    preg_match('/.*\s([A-Za-z]+)$/', $header, $matches);
                    if (!isset($matches[1])) {
                        throw new \InvalidArgumentException('Invalid header: ' . $header . ' on file ' . '');
                    }
                    $category = $matches[1];

                    // todo: wrong!
                    $question = $questionCard->filter('.content > p')->text();

                    $answers = $questionCard->filter('h3:contains("choices were")')
                        ->ancestors()
                        ->eq(0)
                        ->filter('ul > li')
                        ->each(
                            static fn (Crawler $answer): string => $answer->text(),
                        );
//                    var_dump($answers);

                    // todo: wrong!
                    $correctHeader = $questionCard->filter('h3:contains("Correct")');
                    if ($correctHeader->count() === 0) {
                        $correctAnswers = [];
                    } else {
                        $correctAnswers = $correctHeader
                            ->ancestors()
                            ->eq(0)
                            ->filter('ul > li')
                            ->each(
                                static fn (Crawler $answer): string => $answer->text(),
                            );
                    }

                    return new Question($question, $answers, $correctAnswers ,'', $category);
                },
            );

        return array_filter($questions);
    }
}
