<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Intervention\Image\Exception\NotReadableException;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for process document downscale.
 */
class DocumentDownscaleCommand extends Command
{
    private $configuration;
    private $entityManager;
    private $downscaler;

    protected function configure()
    {
        $this->setName('documents:downscale')
            ->setDescription('Downscale every document according to max pixel size defined in configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        /** @var Packages $packages */
        $packages = $this->getHelper('assetPackages')->getPackages();
        $this->configuration = $this->getHelper('configuration')->getConfiguration();
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        if (!empty($this->configuration['assetsProcessing']['maxPixelSize']) &&
            $this->configuration['assetsProcessing']['maxPixelSize'] > 0) {
            $this->downscaler = new DownscaleImageManager(
                $this->entityManager,
                $packages,
                null,
                $this->configuration['assetsProcessing']['driver'],
                $this->configuration['assetsProcessing']['maxPixelSize']
            );

            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to downscale all your image documents to ' . $this->configuration['assetsProcessing']['maxPixelSize'] . 'px?</question>',
                false
            );
            if ($io->askQuestion(
                $confirmation
            )) {
                /** @var Document[] $documents */
                $documents = $this->entityManager
                    ->getRepository(Document::class)
                    ->findBy([
                        'mimeType' => [
                            'image/png',
                            'image/jpeg',
                            'image/gif',
                            'image/tiff',
                        ],
                        'raw' => false,
                    ]);
                $io->progressStart(count($documents));

                foreach ($documents as $document) {
                    try {
                        $this->downscaler->processDocumentFromExistingRaw($document);
                    } catch (NotReadableException $exception) {
                        $io->error($exception->getMessage() . ' - ' . (string) $document);
                    }
                    $io->progressAdvance();
                }

                $io->progressFinish();
                $io->success('Every documents have been downscaled, a raw version has been kept.');

                /*
                 * Clear cache documents
                 */
                $assetsClearer = new AssetsClearer($kernel->getPublicCachePath());
                $assetsClearer->clear();
                $io->writeln($assetsClearer->getOutput());
            }
            return 0;
        } else {
            $io->warning('Your configuration is not set for downscaling documents.');
            $io->note('Add <info>assetsProcessing.maxPixelSize</info> parameter in your <info>config.yml</info> file.');
            return 1;
        }
    }
}
