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
 * @file FilesExportCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

class FilesExportCommand extends Command
{
    use FilesCommandTrait;

    protected function configure()
    {
        $this
            ->setName('files:export')
            ->setDescription('Export public files, private files and fonts into a single ZIP archive at root dir.');
    }

    /**
     * @param string $appName
     * @return string
     */
    protected function getArchiveFileName($appName = "files_export")
    {
        return $appName . '_' . date('Y-m-d') . '.zip';
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

        $publicFileFolder = $kernel->getPublicFilesPath();
        $privateFileFolder = $kernel->getPrivateFilesPath();
        $fontFileFolder = $kernel->getFontsFilesPath();

        $archiveName = $this->getArchiveFileName($configuration['appNamespace']);

        $zip = new ZipArchive();
        $zip->open($kernel->getRootDir() . '/' . $archiveName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->zipFolder($zip, $publicFileFolder, $this->getPublicFolderName());
        $this->zipFolder($zip, $privateFileFolder, $this->getPrivateFolderName());
        $this->zipFolder($zip, $fontFileFolder, $this->getFontsFolderName());

        // Zip archive will be created only after closing object
        $zip->close();
    }


    /**
     * @param ZipArchive $zip
     * @param $folder
     */
    protected function zipFolder(ZipArchive $zip, $folder, $prefix = "/public")
    {
        $finder = new Finder();
        $files = $finder->files()
            ->in($folder)
            ->ignoreDotFiles(false)
            ->exclude(['fonts', 'private']);

        /**
         * @var SplFileInfo $file
         */
        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $prefix . '/' . $relativePath);
            }
        }
    }
}
