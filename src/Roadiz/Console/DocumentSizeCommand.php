<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\SvgSizeResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentSizeCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;
    /**
     * @var ImageManager
     */
    private $manager;

    protected function configure()
    {
        $this->setName('documents:size')
            ->setDescription('Fetch every document size (width and height) and write it in database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getHelper('entityManager')->getEntityManager();
        /** @var Packages $packages */
        $packages = $this->getHelper('assetPackages')->getPackages();
        $this->io = new SymfonyStyle($input, $output);
        $this->manager = new ImageManager();

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
            $this->updateDocumentSize($document, $packages);
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

    private function updateDocumentSize(Document $document, Packages $packages)
    {
        if ($document->isImage()) {
            $documentPath = $packages->getDocumentFilePath($document);
            try {
                $imageProcess = $this->manager->make($documentPath);
                $document->setImageWidth($imageProcess->width());
                $document->setImageHeight($imageProcess->height());
            } catch (NotReadableException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->io->error($documentPath . ' is not a readable image.');
            }
        } elseif ($document->isSvg()) {
            try {
                $svgSizeResolver = new SvgSizeResolver($document, $packages);
                $document->setImageWidth($svgSizeResolver->getWidth());
                $document->setImageHeight($svgSizeResolver->getHeight());
            } catch (\RuntimeException $exception) {
                $this->io->error($exception->getMessage());
            }
        }
    }
}
