<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DocumentDownscaleCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

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
                    $this->downscaler->processDocumentFromExistingRaw($document);
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
