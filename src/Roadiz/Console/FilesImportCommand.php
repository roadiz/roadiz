<?php
declare(strict_types=1);

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
            return 0;
        } else {
            $io->error('Zip archive does not exist or is invalid.');
            return 1;
        }
    }
}
