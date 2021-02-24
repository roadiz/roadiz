<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Composer\InstallFiles;

use Composer\Script\Event;
use RZ\Roadiz\Config\DotEnvConfigurationHandler;
use RZ\Roadiz\Random\TokenGenerator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class ScriptHandler
{
    /**
     * @param Event $event
     */
    public static function install(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        $fs = new Filesystem();
        $io = $event->getIO();

        if (!isset($extras['install-files'])) {
            $io->write('No dirs or files configured through the extra.install-files setting.');
            return;
        }

        $files = $extras['install-files'];

        if ($files === array_values($files)) {
            throw new \InvalidArgumentException('The extra.install-files must be hash like "{<dir_or_file_from>: <dir_to>}".');
        }

        foreach ($files as $from => $to) {
            if (false === file_exists($from)) {
                throw new \InvalidArgumentException(sprintf('<error>Source directory or file "%s" does not exist.</error>', $from));
            }
            if (file_exists($to) && !is_dir($to)) {
                $io->write(sprintf('<comment>%s</comment> already exists.', $to));
            } else {
                // Check the renaming of file for direct moving (file-to-file)
                $isCopyingFile = substr($to, -1) !== '/' && !is_dir($from);
                if (file_exists($to) && !is_dir($to) && !$isCopyingFile) {
                    throw new \InvalidArgumentException('Destination directory is not a directory.');
                }
                try {
                    if ($isCopyingFile) {
                        $fs->mkdir(dirname($to));
                    } else {
                        $fs->mkdir($to);
                    }
                } catch (IOException $e) {
                    throw new \InvalidArgumentException(sprintf('<error>Could not create directory %s.</error>', $to));
                }

                if (is_dir($from)) {
                    $finder = new Finder();
                    $finder->files()->ignoreDotFiles(false)->in($from);
                    foreach ($finder as $file) {
                        $dest = sprintf('%s/%s', $to, $file->getRelativePathname());
                        try {
                            $fs->copy($file, $dest);
                        } catch (IOException $e) {
                            throw new \InvalidArgumentException(sprintf('<error>Could not copy %s</error>', $file->getBaseName()));
                        }
                    }
                    $io->write(sprintf('Copied files from <comment>%s</comment> to <comment>%s</comment>.', $from, $to));
                } else {
                    try {
                        if ($isCopyingFile) {
                            $fs->copy($from, $to);
                            $io->write(sprintf('Copied <comment>%s</comment> to <comment>%s</comment>.', $from, $to));
                        } else {
                            $newFile = $to.basename($from);
                            if (!file_exists($newFile)) {
                                $fs->copy($from, $newFile);
                                $io->write(sprintf('Copied <comment>%s</comment> to <comment>%s</comment>.', $from, $to));
                            } else {
                                $io->write(sprintf('<comment>%s</comment> already exists.', $newFile));
                            }
                        }
                    } catch (IOException $e) {
                        throw new \InvalidArgumentException(sprintf('<error>Could not copy %s</error>', $from));
                    }
                }
            }
        }
    }

    public static function rotateSecret(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        $fs = new Filesystem();
        $io = $event->getIO();

        if (!isset($extras['config-path'])) {
            $io->write('No config path configured through the extra.config-path setting.');
            return;
        }

        if (false === $fs->exists($extras['config-path'])) {
            throw new \InvalidArgumentException(sprintf('<error>File "%s" does not exist.</error>', $extras['config-path']));
        }

        $parser = new Parser();
        $configuration = $parser->parse(file_get_contents($extras['config-path']));

        /*
         * Do not rotate secret if it is a dot-env variable.
         */
        if (preg_match(DotEnvConfigurationHandler::ENV_PATTERN, $configuration['security']['secret']) === 1) {
            return true;
        }

        $generator = new TokenGenerator();
        $configuration['security']['secret'] = $generator->generateToken();

        try {
            $dumper = new Dumper();
            $yaml = $dumper->dump($configuration, 4);
            file_put_contents($extras['config-path'], $yaml);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }
}
