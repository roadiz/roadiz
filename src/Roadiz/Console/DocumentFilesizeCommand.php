<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class DocumentFilesizeCommand extends Command
{
    protected SymfonyStyle $io;

    protected function configure()
    {
        $this->setName('documents:file:size')
            ->setDescription('Fetch every document file size (in bytes) and write it in database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        /** @var Packages $packages */
        $packages = $this->getHelper('assetPackages')->getPackages();
        $this->io = new SymfonyStyle($input, $output);

        $batchSize = 20;
        $i = 0;
        $count = $em->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->select('count(d)')
            ->getQuery()
            ->getSingleScalarResult();
        $q = $em->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->getQuery();
        $iterableResult = $q->iterate();

        $this->io->progressStart($count);
        foreach ($iterableResult as $row) {
            /** @var Document $document */
            $document = $row[0];
            $this->updateDocumentFilesize($document, $packages);
            if (($i % $batchSize) === 0) {
                $em->flush(); // Executes all updates.
                $em->clear(); // Detaches all objects from Doctrine!
            }
            ++$i;
            $this->io->progressAdvance();
        }
        $em->flush();
        $this->io->progressFinish();
        return 0;
    }

    private function updateDocumentFilesize(Document $document, Packages $packages)
    {
        if (null !== $document->getRelativePath()) {
            $documentPath = $packages->getDocumentFilePath($document);
            try {
                $file = new File($documentPath);
                $document->setFilesize($file->getSize());
            } catch (FileNotFoundException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->io->error($documentPath . ' file not found.');
            }
        }
    }
}
