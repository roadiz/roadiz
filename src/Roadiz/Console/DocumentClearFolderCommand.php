<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentClearFolderCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    protected function configure()
    {
        $this->setName('documents:clear-folder')
            ->addArgument('folderId', InputArgument::REQUIRED, 'Folder ID to delete documents from.')
            ->setDescription('Delete every document from folder. <info>Danger zone</info>')
        ;
    }

    protected function getDocumentQueryBuilder(ObjectManager $entityManager, Folder $folder): QueryBuilder
    {
        $qb = $entityManager->getRepository(Document::class)->createQueryBuilder('d');
        return $qb->innerJoin('d.folders', 'f')
            ->andWhere($qb->expr()->eq('f.id', ':folderId'))
            ->setParameter(':folderId', $folder);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        $this->io = new SymfonyStyle($input, $output);

        $folderId = (int) $input->getArgument('folderId');
        if ($folderId <= 0) {
            throw new \InvalidArgumentException('Folder ID must be a valid ID');
        }
        /** @var Folder|null $folder */
        $folder = $em->find(Folder::class, $folderId);
        if ($folder === null) {
            throw new \InvalidArgumentException(sprintf('Folder #%d does not exist.', $folderId));
        }

        $batchSize = 20;
        $i = 0;

        $count = $this->getDocumentQueryBuilder($em, $folder)
            ->select('count(d)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($count <= 0) {
            $this->io->warning('No documents were found in this folder.');
            return 0;
        }

        if ($this->io->askQuestion(new ConfirmationQuestion(
            sprintf('Are you sure to delete permanently %d documents?', $count),
            false
        ))) {
            $results = $this->getDocumentQueryBuilder($em, $folder)
                ->select('d')
                ->getQuery()
                ->getResult();

            $this->io->progressStart($count);
            /** @var Document $document */
            foreach ($results as $document) {
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
