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
                    return $this->parseQuestionCard($questionCard);
                },
            );

        return array_filter($questions);
    }

    private function parseQuestionCard(Crawler $questionCard): ?Question
    {
        $header = $questionCard->filter('.header');
        $headerText = $header->text();
        if (str_contains($headerText, 'Congratulation')) {
            return null;
        }
        preg_match('/.*\s([A-Za-z]+)$/', $headerText, $matches);
        if (!isset($matches[1])) {
            throw new \InvalidArgumentException('Invalid header: ' . $headerText);
        }
        $category = $matches[1];

        $start = $questionCard->filter('.content > p')->first();
        $question = $start->text(null, false);
        foreach ($start->nextAll() as $element) {
            $elementCrawler = new Crawler($element);
            if (str_contains($element->textContent, 'Correct') || str_contains($element->textContent, 'Wrong')) {
                break;
            }
            $question .= "\n" .$elementCrawler->text(null, false);
        }

        $answers = $questionCard->filter('h3:contains("choices were")')
            ->ancestors()
            ->eq(0)
            ->filter('ul > li')
            ->each(
                static fn (Crawler $answer): string => $answer->text(),
            );

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
    }
}
