<?php

declare(strict_types=1);

namespace App\Command;

use App\Aggregator\QuestionToCategoriesAggregator;
use App\Parser\QuestionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->addArgument('input-dir', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getFiles($input);
        $questionGroups = [];
        foreach ($files as $file) {
            $questionGroups[] = $this->questionParser->parse($file->getContents());
        }
        $questions = \array_merge(...$questionGroups);

        $categoryCollection = $this->questionToCategoriesAggregator->aggregate($questions);

        foreach ($categoryCollection as $category) {
            $this->filesystem->dumpFile(
                'var/data/' . $category->getName() . '.yaml',
                $this->serializer->serialize($category, 'yaml', ['yaml_inline' => 4]),
            );
        }

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
}
