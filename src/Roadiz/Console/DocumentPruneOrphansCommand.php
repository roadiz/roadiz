<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class DocumentPruneOrphansCommand extends Command
{
    protected SymfonyStyle $io;

    protected function configure()
    {
        $this->setName('documents:prune:orphans')
            ->setDescription('Remove any document without existing file on filesystem, except embeds. <info>Danger zone</info>')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE)
        ;
    }

    /**
     * @return QueryBuilder
     */
    protected function getDocumentQueryBuilder(): QueryBuilder
    {
        /** @var ObjectManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        return $em->getRepository(Document::class)->createQueryBuilder('d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        /** @var Packages $packages */
        $packages = $this->getHelper('assetPackages')->getPackages();
        $filesystem = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $this->io->note('Dry run');
        }
        $deleteCount = 0;
        $batchSize = 20;
        $i = 0;
        $count = $this->getDocumentQueryBuilder()
            ->select('count(d)')
            ->getQuery()
            ->getSingleScalarResult();
        $q = $this->getDocumentQueryBuilder()->getQuery();
        $iterableResult = $q->iterate();

        $this->io->progressStart($count);
        foreach ($iterableResult as $row) {
            /** @var Document $document */
            $document = $row[0];
            $this->checkDocumentFilesystem($document, $packages, $filesystem, $em, $deleteCount, $dryRun);
            if (($i % $batchSize) === 0 && !$dryRun) {
                $em->flush(); // Executes all updates.
                $em->clear(); // Detaches all objects from Doctrine!
            }
            ++$i;
            $this->io->progressAdvance();
        }
        if (!$dryRun) {
            $em->flush();
        }
        $this->io->progressFinish();
        $this->io->success(sprintf('%d documents were deleted.', $deleteCount));
        return 0;
    }

    /**
     * @param Document $document
     * @param Packages $packages
     * @param Filesystem $filesystem
     * @param ObjectManager $entityManager
     * @param int $deleteCount
     * @param bool $dryRun
     */
    private function checkDocumentFilesystem(
        Document $document,
        Packages $packages,
        Filesystem $filesystem,
        ObjectManager $entityManager,
        int &$deleteCount,
        bool $dryRun = false
    ): void {
        /*
         * Do not prune embed documents which may not have any file
         */
        if (!$document->isEmbed()) {
            $documentPath = $packages->getDocumentFilePath($document);
            if (!$filesystem->exists($documentPath)) {
                if ($this->io->isDebug() && !$this->io->isQuiet()) {
                    $this->io->writeln(sprintf('%s file does not exist, pruning document %s', $document->getRelativePath(), $document->getId()));
                }
                if (!$dryRun) {
                    $entityManager->remove($document);
                    $deleteCount++;
                }
            }
        }
    }
}
