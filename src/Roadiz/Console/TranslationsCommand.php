<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing translations from terminal.
 */
class TranslationsCommand extends Command
{
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:list')
            ->setDescription('List translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $io = new SymfonyStyle($input, $output);
        $translations = $this->entityManager
            ->getRepository(Translation::class)
            ->findAll();

        if (count($translations) > 0) {
            $tableContent = [];
            /** @var Translation $trans */
            foreach ($translations as $trans) {
                $tableContent[] = [
                    $trans->getId(),
                    $trans->getName(),
                    $trans->getLocale(),
                    (!$trans->isAvailable() ? 'X' : ''),
                    ($trans->isDefaultTranslation() ? 'X' : ''),
                ];
            }
            $io->table(['Id', 'Name', 'Locale', 'Disabled', 'Default'], $tableContent);
        } else {
            $io->error('No available translations.');
        }
        return 0;
    }
}
