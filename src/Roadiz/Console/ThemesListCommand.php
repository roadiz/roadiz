<?php
declare(strict_types=1);
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file ThemesListCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemesListCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected function configure()
    {
        $this->setName('themes:list')
            ->setDescription('Installed themes')
            ->addArgument(
                'classname',
                InputArgument::OPTIONAL,
                'Main theme classname (Use / instead of \\ and do not forget starting slash)'
            );
    }

    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->getHelper('themeResolver')->getThemeResolver();
        $name = $input->getArgument('classname');

        $tableContent = [];

        if ($name) {
            /*
             * Replace slash by anti-slashes
             */
            $name = str_replace('/', '\\', $name);
            $theme = $themeResolver->findThemeByClass($name);
            $tableContent[] = [
                str_replace('\\', '/', $theme->getClassName()),
                ($theme->isAvailable() ? 'X' : ''),
                ($theme->isBackendTheme() ? 'Backend' : 'Frontend'),
            ];
        } else {
            $themes = $themeResolver->findAll();
            if (count($themes) > 0) {
                foreach ($themes as $theme) {
                    $tableContent[] = [
                        str_replace('\\', '/', $theme->getClassName()),
                        ($theme->isAvailable() ? 'X' : ''),
                        ($theme->isBackendTheme() ? 'Backend' : 'Frontend'),
                    ];
                }
            } else {
                $io->warning('No available themes');
            }
        }

        $io->table(['Class (with / instead of \)', 'Enabled', 'Type'], $tableContent);
        return 0;
    }
}
