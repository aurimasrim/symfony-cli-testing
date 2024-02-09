<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use App\HtmlParsing\Aggregator\QuestionToCategoriesAggregator;
use App\HtmlParsing\Parser\QuestionParser;
use Certificationy\Loaders\YamlLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Serializer\SerializerInterface;

class PopulateDatabase extends Command
{
    protected static $defaultName = 'app:populate-database';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly QuestionParser $questionParser,
        private readonly QuestionToCategoriesAggregator $questionToCategoriesAggregator,
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yamlLoader = new YamlLoader(['var/symfony-cert-test-data/data']);
        $questions = $yamlLoader->all();

        $categories = []; 
        foreach ($questions as $inputQuestion) {
            $questionCategory = $inputQuestion->getCategory();
            if (isset($categories[$questionCategory])) {
                $category = $categories[$questionCategory];
            } else {
                $category = new Category($questionCategory);
                $this->entityManager->persist($category);

                $categories[$questionCategory] = $category;
            }

            $questionValue = $inputQuestion->getQuestion();
            $question = new Question($questionValue, $category, $inputQuestion->getHelp());
            $this->entityManager->persist($question);
            foreach ($inputQuestion->getAnswers() as $inputAnswer) {
                $answer = new Answer($question, $inputAnswer->getValue(), $inputAnswer->isCorrect());    
                $this->entityManager->persist($answer);
            }
        }

        $this->entityManager->flush();

        

        // $output->writeln('Categories converted: ' . \count($categoryCollection));
        // $output->writeln('Questions converted: ' . $categoryCollection->countQuestions());


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
