<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\AverageColorResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentAverageColorCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;
    /**
     * @var ImageManager
     */
    private $manager;
    /**
     * @var AverageColorResolver
     */
    private $colorResolver;

    protected function configure()
    {
        $this->setName('documents:color')
            ->setDescription('Fetch every document medium color and write it in database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        /** @var Packages $packages */
        $packages = $this->getHelper('assetPackages')->getPackages();
        $this->io = new SymfonyStyle($input, $output);
        $this->manager = new ImageManager();
        $this->colorResolver = new AverageColorResolver();

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
            $this->updateDocumentColor($document, $packages);
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

    private function updateDocumentColor(Document $document, Packages $packages)
    {
        if ($document->isImage()) {
            $documentPath = $packages->getDocumentFilePath($document);
            try {
                $mediumColor = $this->colorResolver->getAverageColor($this->manager->make($documentPath));
                $document->setImageAverageColor($mediumColor);
            } catch (NotReadableException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->io->error($documentPath . ' is not a readable image.');
            }
        }
    }
}
