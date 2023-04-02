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

        return \array_values(array_filter($questions));
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
                if (!str_starts_with($result, '<?php')) {
                    $elementText = "<?php\n" . $elementText;
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

//            return [];
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
