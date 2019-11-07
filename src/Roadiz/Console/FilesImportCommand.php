<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file FilesImportCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class FilesImportCommand extends Command
{
    use FilesCommandTrait;

    protected function configure()
    {
        $this
            ->setName('files:import')
            ->setDescription('Import public files, private files and fonts from a single ZIP archive.')
            ->setDefinition([
                new InputArgument('input', InputArgument::REQUIRED, 'ZIP file path to import.'),
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $configuration = $this->getHelper('configuration')->getConfiguration();
        $questionHelper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $confirmation = new ConfirmationQuestion(
            '<question>Are you sure to import files from this archive? Your existing files will be lost!</question>',
            false
        );

        $tempDir = tempnam(sys_get_temp_dir(), $configuration['appNamespace'] . '_files');
        if (file_exists($tempDir)) {
            unlink($tempDir);
        }
        mkdir($tempDir);

        $zipArchivePath = $input->getArgument('input');
        $zip = new ZipArchive();
        if (true === $zip->open($zipArchivePath)) {
            if ($io->askQuestion(
                $confirmation
            )) {
                $zip->extractTo($tempDir);

                $fs = new Filesystem();
                if ($fs->exists($tempDir . $this->getPublicFolderName())) {
                    $fs->mirror($tempDir . $this->getPublicFolderName(), $kernel->getPublicFilesPath());
                    $io->success('Public files have been imported.');
                }
                if ($fs->exists($tempDir . $this->getPrivateFolderName())) {
                    $fs->mirror($tempDir . $this->getPrivateFolderName(), $kernel->getPrivateFilesPath());
                    $io->success('Private files have been imported.');
                }
                if ($fs->exists($tempDir . $this->getFontsFolderName())) {
                    $fs->mirror($tempDir . $this->getFontsFolderName(), $kernel->getFontsFilesPath());
                    $io->success('Font files have been imported.');
                }

                $fs->remove($tempDir);
            }
        } else {
            $io->error('Zip archive does not exist or is invalid.');
        }
    }
}
