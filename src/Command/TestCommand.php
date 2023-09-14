<?php

declare(strict_types=1);

namespace App\Command;

use Certificationy\Collections\Questions;
use App\Testing\YamlLoader as Loader;
use Certificationy\Set;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

/**
 * Based on Certificationy\Cli\Command\StartCommand
 */
class TestCommand extends Command
{
    protected static $defaultName = 'certificationy:test';

    /**
     * @var int
     */
    public const WORDWRAP_NUMBER = 80;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('start')
            ->setDescription('Starts a new question set')
            ->addArgument('categories', InputArgument::IS_ARRAY, 'Which categories do you want (separate multiple with a space)', [])
            ->addOption('number', null, InputOption::VALUE_OPTIONAL, 'How many questions do you want?', '20')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List categories')
            ->addOption('training', null, InputOption::VALUE_NONE, 'Training mode: the solution is displayed after each question')
            ->addOption('hide-multiple-choice', null, InputOption::VALUE_NONE, 'Should we hide the information that the question is multiple choice?')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Use custom config', 'config.yml')
            ->addOption('only-unanswered', null, InputOption::VALUE_NONE, 'Only ask questions that have not been answered correctly yet')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->path(is_string($input->getOption('config')) ? $input->getOption('config') : null);
        $fileContent = (string) file_get_contents($config);
        $paths = Yaml::parse($fileContent);

        $yamlLoader = new Loader($paths);
        $onlyUnanswered = (bool)$input->getOption('only-unanswered');

        if ($input->getOption('list')) {
            $output->writeln($yamlLoader->categories($onlyUnanswered));

            return 1;
        }

        $categories = (array) $input->getArgument('categories');
        $number = (int) $input->getOption('number');

        $set = Set::create($yamlLoader->load($number, $categories, $onlyUnanswered)->get($number, $onlyUnanswered));

        if ($set->getQuestions()->count() > 0) {
            $output->writeln(
                sprintf('Starting a new set of <info>%s</info> questions (available questions: <info>%s</info>)', count($set->getQuestions()), count($yamlLoader->all()))
            );

            $this->askQuestions($set, $input, $output);
            $this->displayResults($set, $output);
        } else {
            $output->writeln('<error>✗</error> No questions can be found.');
        }

        return 0;
    }

    /**
     * Ask questions
     *
     * @param Set $set A Certificationy questions Set instance
     * @param InputInterface $input A Symfony Console input instance
     * @param OutputInterface $output A Symfony Console output instance
     */
    protected function askQuestions(Set $set, InputInterface $input, OutputInterface $output): void
    {
        $questionHelper = $this->getHelper('question');
        $hideMultipleChoice = $input->getOption('hide-multiple-choice');
        $questionCount = 1;

        foreach ($set->getQuestions()->all() as $i => $question) {
            $questionValue = $question->getQuestion();
            $choiceQuestion = new ChoiceQuestion(
                sprintf(
                    'Question <comment>#%d</comment> [<info>%s</info>] %s %s' . "\n",
                    $questionCount++,
                    $question->getCategory(),
                    $questionValue,
                    ($hideMultipleChoice === true ? '' : "\n" . 'This question <comment>' . ($question->isMultipleChoice() === true ? 'IS' : 'IS NOT') . '</comment> multiple choice.')
                ),
                $question->getAnswersLabels()
            );

            $multiSelect = true === $hideMultipleChoice ? true : $question->isMultipleChoice();
            $numericOnly = 1 === array_product(array_map('is_numeric', $question->getAnswersLabels()));
            $choiceQuestion->setMultiselect($multiSelect);
            $choiceQuestion->setErrorMessage('Answer %s is invalid.');
            $choiceQuestion->setAutocompleterValues($numericOnly ? null : $question->getAnswersLabels());

            $answer = $questionHelper->ask($input, $output, $choiceQuestion);

            $answers = true === $multiSelect ? $answer : [$answer];
            $answer = true === $multiSelect ? implode(', ', $answer) : $answer;

            $set->setUserAnswers($i, $answers);

            if ($input->getOption('training')) {
                $uniqueSet = Set::create(new Questions([$i => $question]));

                $uniqueSet->setUserAnswers($i, $answers);

                $this->displayResults($uniqueSet, $output);
            }

            $output->writeln(sprintf('<comment>✎ Your answer</comment>: %s', $answer));
            $output->writeln('');
        }


    }

    /**
     * Returns results
     *
     * @param Set $set A Certificationy questions Set instance
     * @param OutputInterface $output A Symfony Console output instance
     */
    protected function displayResults(Set $set, OutputInterface $output): void
    {
        $results = [];

        $questionCount = 0;

        foreach ($set->getQuestions()->all() as $key => $question) {
            $isCorrect = $set->isCorrect($key);
            ++$questionCount;
            $label = wordwrap($question->getQuestion(), self::WORDWRAP_NUMBER, "\n");
            $help = $question->getHelp();

            $results[] = [
                sprintf('<comment>#%d</comment> [<info>%s</info>] %s', $questionCount, $question->getCategory(), $label),
                wordwrap(implode(",\n", $question->getCorrectAnswersValues()), self::WORDWRAP_NUMBER, "\n"),
                $isCorrect ? '<info>✔</info>' : '<error>✗</error>',
                (null !== $help) ? wordwrap($help, self::WORDWRAP_NUMBER, "\n") : '',
            ];
        }

        if ($results) {
            $tableHelper = new Table($output);
            $tableHelper
                ->setHeaders(['Question', 'Correct answer', 'Result', 'Help'])
                ->setRows($results)
            ;

            $tableHelper->render();

            $output->writeln(
                sprintf('<comment>Results</comment>: <error>errors: %s</error> - <info>correct: %s</info>', $set->getWrongAnswers()->count(), $set->getCorrectAnswers()->count())
            );
        }
    }

    /**
     * Returns configuration file path
     *
     * @return string $path      The configuration filepath
     */
    protected function path(?string $config = null): string
    {
        $defaultConfig = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . 'config.yml'
        ;

        return $config ?? $defaultConfig;
    }

}
