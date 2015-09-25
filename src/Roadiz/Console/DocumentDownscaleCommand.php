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

use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $kernel = $this->getHelperSet()->get('kernel')->getKernel();
        $this->configuration = $this->getHelperSet()->get('configuration')->getConfiguration();
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $this->questionHelper = $this->getHelperSet()->get('question');

        if (!empty($this->configuration['assetsProcessing']['maxPixelSize']) &&
            $this->configuration['assetsProcessing']['maxPixelSize'] > 0) {
            $this->downscaler = new DownscaleImageManager(
                $this->entityManager,
                null,
                $this->configuration['assetsProcessing']['driver'],
                $this->configuration['assetsProcessing']['maxPixelSize']
            );

            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to downscale all your image documents to ' . $this->configuration['assetsProcessing']['maxPixelSize'] . 'px?</question>',
                false
            );
            if ($this->questionHelper->ask(
                $input,
                $output,
                $confirmation
            )) {
                $documents = $this->entityManager
                    ->getRepository('RZ\Roadiz\Core\Entities\Document')
                    ->findBy([
                        'mimeType' => [
                            'image/png',
                            'image/jpeg',
                            'image/gif',
                            'image/tiff',
                        ],
                        'raw' => false,
                    ]);
                $progress = new ProgressBar($output, count($documents));
                $progress->setFormat('verbose');
                $progress->start();

                foreach ($documents as $document) {
                    $this->downscaler->processDocumentFromExistingRaw($document);
                    $progress->advance();
                }

                $progress->finish();
                $text = PHP_EOL . '<info>Every documents have been downscaled, a raw version has been kept.</info>' . PHP_EOL;

                /*
                 * Clear cache documents
                 */
                $assetsClearer = new AssetsClearer($kernel->getCacheDir());
                $assetsClearer->clear();
                $text .= $assetsClearer->getOutput() . PHP_EOL;
            }
        } else {
            $text = '<info>Your configuration is not set for downscaling documents.</info>' . PHP_EOL;
            $text .= 'Add <info>assetsProcessing.maxPixelSize</info> parameter in your <info>config.yml</info> file.' . PHP_EOL;
        }

        $output->writeln($text);
    }
}
