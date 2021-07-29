<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentPruneCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    protected function configure()
    {
        $this->setName('documents:prune:unused')
            ->setDescription('Delete every document not used by a setting, a node-source, a tag or an attribute. <info>Danger zone</info>')
        ;
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @return Document[]
     */
    protected function getDocuments(EntityManagerInterface $entityManager): array
    {
        return $entityManager->getRepository(Document::class)->findAllUnused();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        $this->io = new SymfonyStyle($input, $output);

        $batchSize = 20;
        $i = 0;

        $documents = $this->getDocuments($em);
        $count = count($documents);

        if ($count <= 0) {
            $this->io->warning('All documents are used.');
            return 0;
        }

        if ($this->io->askQuestion(new ConfirmationQuestion(
            sprintf('Are you sure to delete permanently %d unused documents?', $count),
            false
        ))) {
            $this->io->progressStart($count);
            /** @var Document $document */
            foreach ($documents as $document) {
                $em->remove($document);
                if (($i % $batchSize) === 0) {
                    $em->flush(); // Executes all updates.
                }
                ++$i;
                $this->io->progressAdvance();
            }
            $em->flush();
            $this->io->progressFinish();
        }

        return 0;
    }
}
