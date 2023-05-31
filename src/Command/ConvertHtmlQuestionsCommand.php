<?php

declare(strict_types=1);

namespace App\Command;

use App\Aggregator\QuestionToCategoriesAggregator;
use App\DTO\Question;
use App\DTO\QuestionCollection;
use App\Parser\QuestionParser;
use Certificationy\Collections\Questions;
use Certificationy\Interfaces\QuestionInterface;
use Certificationy\Loaders\YamlLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Serializer\SerializerInterface;

class ConvertHtmlQuestionsCommand extends Command
{
    protected static $defaultName = 'app:convert-html-questions';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly QuestionParser $questionParser,
        private readonly QuestionToCategoriesAggregator $questionToCategoriesAggregator,
        private readonly SerializerInterface $serializer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('input-dir', InputArgument::REQUIRED)
            ->addOption('include-without-answers', null, InputOption::VALUE_NONE, 'Include questions without correct answers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // todo: read existing questions and merge with new ones
        $yamlLoader = new YamlLoader(['var/data']);
        $questions = $yamlLoader->all();
        $existingQuestions = $this->convertQuestions($questions);


        $files = $this->getFiles($input);
        $questions = new QuestionCollection();
        foreach ($files as $file) {
            $questions->merge(
                $this->questionParser->parse(
                    $file->getContents(),
                    (bool)$input->getOption('include-without-answers'),
                ),
            );
        }

        $questions = $existingQuestions->mergeUnique($questions);
        $categoryCollection = $this->questionToCategoriesAggregator->aggregate($questions);

        foreach ($categoryCollection as $category) {
            $this->filesystem->dumpFile(
                'var/data/' . $category->getName() . '.yaml',
                $this->serializer->serialize($category, 'yaml', ['yaml_inline' => 4]),
            );
        }

        $output->writeln('Categories converted: ' . \count($categoryCollection));
        $output->writeln('Questions converted: ' . $categoryCollection->countQuestions());


        return 0;
    }

    /**
     * @param InputInterface $input
     * @return iterable<SplFileInfo>
     */
    private function getFiles(InputInterface $input): iterable
    {
        $inputDir = (string)$input->getArgument('input-dir');
        return Finder::create()
            ->in($inputDir)
            ->files()
            ->depth(0)
            ->getIterator();
    }

    private function convertQuestions(Questions $questions): QuestionCollection
    {
        $questionCollection = new QuestionCollection();
        foreach ($questions as $question) {
            $questionCollection->add($this->convertQuestion($question));
        }

        return $questionCollection;
    }

    private function convertQuestion(QuestionInterface $question): Question
    {
        return new Question(
            $question->getQuestion(),
            $question->getAnswersLabels(),
            $question->getCorrectAnswersValues(),
            $question->getHelp(),
            $question->getCategory(),
        );
    }
}
