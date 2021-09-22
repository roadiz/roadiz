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
 * Command line utils for managing translations.
 */
class TranslationsDeleteCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:delete')
            ->setDescription('Delete a translation')
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
        $translationCount = $this->entityManager
            ->getRepository(Translation::class)
            ->countBy([]);

        if ($translationCount < 2) {
            $io->error('You cannot delete the only one available translation!');
            return 1;
        } elseif ($translation !== null) {
            $io->note('///////////////////////////////' . PHP_EOL .
                '/////////// WARNING ///////////' . PHP_EOL .
                '///////////////////////////////' . PHP_EOL .
                'This operation cannot be undone.' . PHP_EOL .
                'Deleting a translation, you will automatically delete every translated tags, node-sources, url-aliases and documents.');
            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to delete ' . $translation->getName() . ' (' . $translation->getLocale() . ') translation?</question>',
                false
            );
            if ($io->askQuestion(
                $confirmation
            )) {
                $this->entityManager->remove($translation);
                $this->entityManager->flush();
                $io->success('Translation deleted.');
            }
        } else {
            $io->error('Translation for locale ' . $locale . ' does not exist.');
            return 1;
        }
        return 0;
    }
}
