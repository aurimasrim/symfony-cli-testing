<?php

declare(strict_types=1);

namespace App\Parser;

use App\DTO\Question;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;
use Symfony\Component\DomCrawler\Crawler;

class QuestionParser
{
    public function __construct(
        private readonly Highlighter $highlighter,
    ) {
    }

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
        try {
            $category = $this->parseCategory($questionCard);
            $question = $this->parseQuestionValue($questionCard);
            $answers = $this->parseAnswers($questionCard);
            $correctAnswers = $this->parseCorrectAnswers($questionCard);

            return new Question($question, $answers, $correctAnswers ,'', $category);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function parseCategory(Crawler $questionCard): string
    {
        $header = $questionCard->filter('.header');
        $headerText = $header->text();
        if (str_contains($headerText, 'Congratulation')) {
            throw new \InvalidArgumentException('Invalid header: ' . $headerText);
        }
        preg_match('/.*\s([A-Za-z]+)$/', $headerText, $matches);
        if (!isset($matches[1])) {
            throw new \InvalidArgumentException('Invalid header: ' . $headerText);
        }

        return $matches[1];
    }

    private function parseQuestionValue(Crawler $questionCard): string
    {
        $start = $questionCard->filter('.content > p')->first();
        $questionFirstLine = $start->text(null, false);
        $questionNextLines = '';
        foreach ($start->nextAll() as $element) {
            $elementCrawler = new Crawler($element);
            if (str_contains($element->textContent, 'Correct') || str_contains($element->textContent, 'Wrong')) {
                break;
            }
            $questionNextLines .= "\n" . $elementCrawler->text(null, false);
        }
        if (str_ends_with($questionNextLines, 'PHP')) {
            if (!str_starts_with($questionNextLines, '<?php')) {
                $questionNextLines = "<?php\n" . $questionNextLines;
            }
            $questionNextLines = $this->highlighter->getWholeFile($questionNextLines);
        }
        return $questionFirstLine . $questionNextLines;
    }

    /**
     * @return string[]
     */
    private function parseAnswers(Crawler $questionCard): array
    {
        return $questionCard->filter('h3:contains("choices were")')
            ->ancestors()
            ->eq(0)
            ->filter('ul > li')
            ->each(
                static fn (Crawler $answer): string => $answer->text(),
            );
    }

    /**
     * @return string[]
     */
    private function parseCorrectAnswers(Crawler $questionCard): array
    {
        $correctHeader = $questionCard->filter('h3:contains("Correct")');
        if ($correctHeader->count() === 0) {

            return [];
            // todo: wrong!
            throw new \InvalidArgumentException('No correct answers');
        }

        return $correctHeader
            ->ancestors()
            ->eq(0)
            ->filter('ul > li')
            ->each(
                static fn (Crawler $answer): string => $answer->text(),
            );
    }
}
