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
    public function parse(string $html, bool $includeWithoutCorrectAnswers = false): array
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $questions = $crawler->filter('div.ui.card')
            ->each(
                function (Crawler $questionCard) use ($includeWithoutCorrectAnswers): ?Question {
                    return $this->parseQuestionCard($questionCard, $includeWithoutCorrectAnswers);
                },
            );

        return \array_values(
            array_filter(
                $questions,
            )
        );
    }

    private function parseQuestionCard(Crawler $questionCard, bool $includeWithoutCorrectAnswers): ?Question
    {
        try {
            $category = $this->parseCategory($questionCard);
            $question = $this->parseQuestionValue($questionCard);
            $answers = $this->parseAnswers($questionCard);
            $correctAnswers = $this->parseCorrectAnswers($questionCard, $includeWithoutCorrectAnswers);
            $help = $this->parseHelp($questionCard);

            return new Question($question, $answers, $correctAnswers, $help, $category);
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
        $contentCard = $questionCard->filter('.content')->eq(1);
        $result = '';
        foreach ($contentCard->children() as $i => $element) {
            $elementCrawler = new Crawler($element);
            $elementText = $elementCrawler->text(null, false);
            if ($elementCrawler->matches('h2') || $elementCrawler->matches('.right.floated')) {
                continue;
            }
            if (str_contains($elementText, 'Correct') || str_contains($elementText, 'Wrong')) {
                break;
            }

            if ($elementCrawler->matches('.code-toolbar') && $elementCrawler->children('.language-php')->count() > 0) {
                if (!str_starts_with($elementText, '<?php')) {
                    $elementText = "<?php\n" . $elementText;
                }
                if (str_ends_with($elementText, 'PHP')) {
                    $elementText = substr($elementText, 0, -3);
                }
                $elementText = $this->highlighter->getWholeFile($elementText);
            }
            $result .= $elementText . "\n";
        }

        return $result;
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
            ->each(fn (Crawler $answer): string => $this->getAnswerText($answer));
    }

    /**
     * @return string[]
     */
    private function parseCorrectAnswers(Crawler $questionCard, bool $includeWithoutCorrectAnswers): array
    {
        $correctHeader = $questionCard->filter('h3:contains("Correct")');
        if ($correctHeader->count() === 0) {

            return $includeWithoutCorrectAnswers ? [] : throw new \InvalidArgumentException('No correct answers');
        }

        return $correctHeader
            ->ancestors()
            ->eq(0)
            ->filter('ul > li')
            ->each(fn (Crawler $answer): string => $this->getAnswerText($answer));
    }

    private function getAnswerText(Crawler $answer): string
    {
        $resultLines = [];
        foreach ($answer->children() as $i => $element) {
            $elementCrawler = new Crawler($element);
            $elementText = $elementCrawler->text(null, false);

            if ($elementCrawler->matches('.code-toolbar') && $elementCrawler->children('.language-php')->count() > 0) {
                if (!str_starts_with($elementText, '<?php')) {
                    if (str_contains($elementText, "\n")) {
                        $elementText = "<?php\n" . $elementText;
                    } else {
                        $elementText = "<?php " . $elementText;
                    }
                }
                if (str_ends_with($elementText, 'PHP')) {
                    $elementText = substr($elementText, 0, -3);
                }
                $elementText = $this->highlighter->getWholeFile($elementText);
            }
            $resultLines[] = $elementText;
        }

        return implode("\n", $resultLines);
    }

    private function parseHelp(Crawler $questionCard): string
    {
        $helpHeader = $questionCard->filter('h3:contains("Help")');
        if ($helpHeader->count() === 0) {
            return '';
        }

        return $helpHeader
            ->ancestors()
            ->eq(0)
            ->children(':not(h3)')
            ->text();
    }
}
