<?php
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
 * @file ThemeInstallCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\DBAL\Exception\TableNotFoundException;
use RZ\Roadiz\Console\Tools\Fixtures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemeInstallCommand extends Command
{
    private $themeRoot;
    private $entityManager;

    protected function configure()
    {
        $this->setName('themes:install')
            ->setDescription('Manage themes installation')
            ->addArgument(
                'classname',
                InputArgument::REQUIRED,
                'Main theme classname (Use / instead of \\ and do not forget starting slash)'
            )
            ->addOption(
                'data',
                null,
                InputOption::VALUE_NONE,
                'Import default data (node-types, roles, settings and tags)'
            )
            ->addOption(
                'nodes',
                null,
                InputOption::VALUE_NONE,
                'Import nodes data. This cannot be done at the same time with --data option.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $classname = $input->getArgument('classname');
        /*
         * Replace slash by anti-slashes
         */
        $classname = str_replace('/', '\\', $classname);

        echo $classname;

        try {
            $theme = $this->getTheme($classname);
            $array = explode('\\', $classname);
            $this->themeRoot = ROADIZ_ROOT . "/themes/" . $array[count($array) - 2];
        } catch (TableNotFoundException $e) {
            $theme = null;
        }

        if ($input->getOption('data')) {
            if (null !== $theme) {
                $this->importThemeData($classname, $text);
            } else {
                throw new \Exception("You cannot import data from a non-existant theme.", 1);
            }
        } elseif ($input->getOption('nodes')) {
            if (null !== $theme) {
                $this->importThemeNodes($classname, $text);
            } else {
                throw new \Exception("You cannot import nodes from a non-existant theme.", 1);
            }
        } else {
            $this->importTheme($classname, $text);
        }

        $output->writeln($text);
    }

    protected function importThemeData($classname, &$text)
    {
        $data = $this->getThemeConfig($classname);

        if (false !== $data && isset($data["importFiles"])) {
            if (isset($data["importFiles"]['roles'])) {
                foreach ($data["importFiles"]['roles'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\RolesImporter::importJsonFile(
                        file_get_contents($this->themeRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $this->themeRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['settings'])) {
                foreach ($data["importFiles"]['settings'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\SettingsImporter::importJsonFile(
                        file_get_contents($this->themeRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $this->themeRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['nodetypes'])) {
                foreach ($data["importFiles"]['nodetypes'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\NodeTypesImporter::importJsonFile(
                        file_get_contents($this->themeRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $this->themeRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['tags'])) {
                foreach ($data["importFiles"]['tags'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\TagsImporter::importJsonFile(
                        file_get_contents($this->themeRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $this->themeRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
            $text .= 'You should do a <info>bin/roadiz orm:schema-tool:update --force</info> to apply your themes into database.' . PHP_EOL;

        } else {
            $text .= '<info>Theme class “' . $classname . '” has no data to import.</info>' . PHP_EOL;
        }
    }

    protected function importThemeNodes($classname, &$text)
    {
        $data = $this->getThemeConfig($classname);

        if (false !== $data && isset($data["importFiles"])) {
            if (isset($data["importFiles"]['nodes'])) {
                foreach ($data["importFiles"]['nodes'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\NodesImporter::importJsonFile(
                        file_get_contents($this->themeRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $this->themeRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
        } else {
            $text .= '<info>Theme class “' . $classname . '” has no nodes to import.</info>' . PHP_EOL;
        }
    }

    protected function getThemeConfig($classname)
    {
        return Yaml::parse($this->themeRoot . "/config.yml");
    }

    protected function importTheme($classname, &$text)
    {
        $kernel = $this->getHelperSet()->get('kernel')->getKernel();
        $themeFile = $classname;
        $themeFile = str_replace('\\', '/', $themeFile);
        $themeFile = str_replace('Themes', 'themes', $themeFile);
        $themeFile .= ".php";

        if (file_exists($themeFile)) {
            $fixtures = new Fixtures(
                $this->entityManager,
                $kernel->getCacheDir(),
                $kernel->isDebug()
            );
            $fixtures->installFrontendTheme($classname);
            $text .= '<info>Theme class “' . $themeFile . '” has been installed…</info>' . PHP_EOL;
        } else {
            $text .= '<error>Theme class “' . $themeFile . '” does not exist.</error>' . PHP_EOL;
        }
    }

    protected function getTheme($classname)
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findOneByClassName($classname);
    }
}
