<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
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

        $fs = new Filesystem();

        $publicFileFolder = $kernel->getPublicFilesPath();
        $privateFileFolder = $kernel->getPrivateFilesPath();
        $fontFileFolder = $kernel->getFontsFilesPath();

        $archiveName = $this->getArchiveFileName($configuration['appNamespace']);

        $zip = new ZipArchive();
        $zip->open($kernel->getRootDir() . '/' . $archiveName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($fs->exists($publicFileFolder)) {
            $this->zipFolder($zip, $publicFileFolder, $this->getPublicFolderName());
        }
        if ($fs->exists($privateFileFolder)) {
            $this->zipFolder($zip, $privateFileFolder, $this->getPrivateFolderName());
        }
        if ($fs->exists($fontFileFolder)) {
            $this->zipFolder($zip, $fontFileFolder, $this->getFontsFolderName());
        }

        // Zip archive will be created only after closing object
        $zip->close();
        return 0;
    }


    /**
     * @param ZipArchive $zip
     * @param string $folder
     * @param string $prefix
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
