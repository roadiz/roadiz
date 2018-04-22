<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file ScriptHandler.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Composer\InstallFiles;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
}
