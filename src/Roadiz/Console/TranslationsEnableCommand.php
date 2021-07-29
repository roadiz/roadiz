<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing translations.
 */
class TranslationsEnableCommand extends Command
{
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:enable')
            ->setDescription('Enables a translation')
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
        $locale = $input->getArgument('locale');

        $translation = $this->entityManager
            ->getRepository(Translation::class)
            ->findOneByLocale($locale);

        if ($translation !== null) {
            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to enable ' . $translation->getName() . ' (' . $translation->getLocale() . ') translation?</question>',
                false
            );
            if ($io->askQuestion(
                $confirmation
            )) {
                $translation->setAvailable(true);
                $this->entityManager->flush();
                $io->success('Translation enabled.');
            }
        } else {
            $io->error('Translation for locale ' . $locale . ' does not exist.');
            return 1;
        }
        return 0;
    }
}
