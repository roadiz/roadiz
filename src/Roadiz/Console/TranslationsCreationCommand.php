<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing translations
 */
class TranslationsCreationCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:create')
            ->setDescription('Create a translation')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Translation name'
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'Translation locale'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $name = $input->getArgument('name');
        $locale = $input->getArgument('locale');

        if ($name) {
            $translationByName = $this->entityManager
                ->getRepository(Translation::class)
                ->findOneByName($name);
            $translationByLocale = $this->entityManager
                ->getRepository(Translation::class)
                ->findOneByLocale($locale);

            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to create ' . $name . ' (' . $locale . ') translation?</question>',
                false
            );

            if (null !== $translationByName) {
                $io->error('Translation ' . $name . ' already exists.');
                return 1;
            } elseif (null !== $translationByLocale) {
                $io->error('Translation locale ' . $locale . ' is already used.');
                return 1;
            } else {
                if ($io->askQuestion(
                    $confirmation
                )) {
                    $newTrans = new Translation();
                    $newTrans->setName($name)
                        ->setLocale($locale);

                    $this->entityManager->persist($newTrans);
                    $this->entityManager->flush();

                    $io->success('New ' . $newTrans->getName() . ' translation for ' . $newTrans->getLocale() . ' locale.');
                }
            }
        }
        return 0;
    }
}
