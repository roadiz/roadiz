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

use Doctrine\ORM\EntityNotFoundException;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\NodesImporter;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\CMS\Importers\TagsImporter;
use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemeInstallCommand extends ThemesCommand
{
    /**
     * @var string
     */
    private $themeRoot;

    /**
     * @var bool
     */
    private $dryRun = false;

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
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Do nothing, only print information.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dry-run')) {
            $this->dryRun = true;
        }

        /*
         * Replace slash by anti-slashes
         */
        $classname = str_replace('/', '\\', $input->getArgument('classname'));
        $reflectionClass = new \ReflectionClass($classname);
        if (!$reflectionClass->isSubclassOf(AppController::class)) {
            throw new RuntimeException('Given theme is not a valid Roadiz theme.');
        }
        $this->themeRoot = call_user_func([$reflectionClass->getName(), 'getThemeFolder']);
        $output->writeln('Theme name is: <info>'. $reflectionClass->getName() .'</info>.');
        $output->writeln('Theme assets are located in <info>'. $this->themeRoot .'/static</info>.');

        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        if ($input->getOption('data')) {
            $this->importThemeData($reflectionClass->getName(), $output);
        } elseif ($input->getOption('nodes')) {
            $this->importThemeNodes($reflectionClass->getName(), $output);
        } else {
            $output->writeln('Frontend themes are no more registered into database. <info>You should use --data or --nodes option.</info>');
        }
    }

    /**
     * @param string $classname
     * @param $text
     */
    protected function importThemeData($classname, OutputInterface $output)
    {
        $data = $this->getThemeConfig();

        if (false !== $data && isset($data["importFiles"])) {
            if (isset($data["importFiles"]['groups'])) {
                foreach ($data["importFiles"]['groups'] as $filename) {
                    if (!$this->dryRun) {
                        GroupsImporter::importJsonFile(
                            file_get_contents($this->themeRoot . "/" . $filename),
                            $this->entityManager,
                            $this->getHelper('handlerFactory')->getHandlerFactory()
                        );
                    }
                    $output->writeln('* Groups file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                }
            }
            if (isset($data["importFiles"]['roles'])) {
                foreach ($data["importFiles"]['roles'] as $filename) {
                    if (!$this->dryRun) {
                        RolesImporter::importJsonFile(
                            file_get_contents($this->themeRoot . "/" . $filename),
                            $this->entityManager,
                            $this->getHelper('handlerFactory')->getHandlerFactory()
                        );
                    }
                    $output->writeln('* Roles file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                }
            }
            if (isset($data["importFiles"]['settings'])) {
                foreach ($data["importFiles"]['settings'] as $filename) {
                    if (!$this->dryRun) {
                        SettingsImporter::importJsonFile(
                            file_get_contents($this->themeRoot . "/" . $filename),
                            $this->entityManager,
                            $this->getHelper('handlerFactory')->getHandlerFactory()
                        );
                    }
                    $output->writeln('* Settings file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                }
            }
            if (isset($data["importFiles"]['nodetypes'])) {
                foreach ($data["importFiles"]['nodetypes'] as $filename) {
                    if (!$this->dryRun) {
                        NodeTypesImporter::importJsonFile(
                            file_get_contents($this->themeRoot . "/" . $filename),
                            $this->entityManager,
                            $this->getHelper('handlerFactory')->getHandlerFactory()
                        );
                    }
                    $output->writeln('* Node-type file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                }
            }
            if (isset($data["importFiles"]['tags'])) {
                foreach ($data["importFiles"]['tags'] as $filename) {
                    if (!$this->dryRun) {
                        try {
                            TagsImporter::importJsonFile(
                                file_get_contents($this->themeRoot . "/" . $filename),
                                $this->entityManager,
                                $this->getHelper('handlerFactory')->getHandlerFactory()
                            );
                            $output->writeln('* Tags file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                        } catch (EntityAlreadyExistsException $e) {
                            $output->writeln('* Tags file <info>' . $this->themeRoot . "/" . $filename . '</info> <error>has NOT been imported ('.$e->getMessage().')</error>.');
                        }
                    } else {
                        $output->writeln('* Tags file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                    }
                }
            }
            $output->writeln('You should do a <info>bin/roadiz generate:nsentities</info> to regenerate your node-types source classes.');
            $output->writeln('And a <info>bin/roadiz orm:schema-tool:update --dump-sql --force</info> to apply your changes into database.');
        } else {
            $output->writeln('Theme class <info>' . $classname . '</info> has no data to import.');
        }
    }

    protected function importThemeNodes($classname, OutputInterface $output)
    {
        $data = $this->getThemeConfig();

        if (false !== $data && isset($data["importFiles"])) {
            if (isset($data["importFiles"]['nodes'])) {
                foreach ($data["importFiles"]['nodes'] as $filename) {
                    try {
                        if (!$this->dryRun) {
                            NodesImporter::importJsonFile(
                                file_get_contents($this->themeRoot . "/" . $filename),
                                $this->entityManager,
                                $this->getHelper('handlerFactory')->getHandlerFactory()
                            );
                        }
                        $output->writeln('— Theme file <info>' . $this->themeRoot . "/" . $filename . '</info> has been imported.');
                    } catch (EntityAlreadyExistsException $e) {
                        $output->writeln('* <error>' . $e->getMessage() . '</error>');
                    } catch (EntityNotFoundException $e) {
                        $output->writeln('* <error>' . $e->getMessage() . '</error>');
                    }
                }
            }
        } else {
            $output->writeln('Theme class <info>' . $classname . '</info> has no nodes to import.');
        }
    }

    /**
     * @return array
     */
    protected function getThemeConfig()
    {
        return Yaml::parse(file_get_contents($this->themeRoot . "/config.yml"));
    }

    /**
     * @param string $classname
     * @param $text
     * @deprecated Frontend themes no more need to be registered in database.
     */
    protected function importTheme($classname, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        if (!$this->dryRun) {
            $fixtures = new Fixtures(
                $this->entityManager,
                $kernel->getCacheDir(),
                $kernel->getRootDir() . '/conf/config.yml',
                $kernel->getRootDir(),
                $kernel->isDebug()
            );
            $fixtures->installFrontendTheme($classname);
        }
        $output->writeln('Theme class <info>' . $classname . '</info> has been registered into database.');
    }
}
