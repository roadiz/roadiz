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
 * @file InstallCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Console\SchemaCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use RZ\Roadiz\Core\Services\DoctrineServiceProvider;
use RZ\Roadiz\Console\Tools\YamlConfiguration;
use RZ\Roadiz\Console\Tools\Fixtures;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class InstallCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install Roadiz roles, settings, translations and default backend theme')
            ->addOption(
                'with-theme',
                null,
                InputOption::VALUE_REQUIRED,
                'Enable the devMode flag for your application'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        if ($input->getOption('no-interaction') ||
            $this->dialog->askConfirmation(
                $output,
                'Before installing Roadiz, did you create database schema? '.PHP_EOL.
                'If not execute: <info>bin/roadiz orm:schema-tool:create</info>'.PHP_EOL.
                '<question>Are you sure to perform installation?</question> : ',
                false
            )
        ) {
            /*
             * Create backend theme
             */
            if (!$this->hasDefaultBackend()) {
                $theme = new Theme();
                $theme->setAvailable(true)
                    ->setBackendTheme(true)
                    ->setClassName("Themes\Rozier\RozierApp");

                Kernel::getService('em')->persist($theme);
                Kernel::getService('em')->flush();

                $text .= '<info>Rozier back-end theme installed…</info>'.PHP_EOL;
            } else {
                $text .= '<error>A back-end theme is already installed.</error>'.PHP_EOL;
            }

            /**
             * Import default data
             */
            $installRoot = ROADIZ_ROOT . "/themes/Install";
            $data = json_decode(file_get_contents($installRoot . "/config.json"), true);
            if (isset($data["importFiles"]['roles'])) {
                foreach ($data["importFiles"]['roles'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\RolesImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename)
                    );
                    $text .= '     — <info>Theme file “'.$installRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['groups'])) {
                foreach ($data["importFiles"]['groups'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\GroupsImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename)
                    );
                    $text .= '     — <info>Theme file “'.$installRoot . "/" .$filename.'” has been imported..</info>'.PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['settings'])) {
                foreach ($data["importFiles"]['settings'] as $filename) {
                    \RZ\Roadiz\CMS\Importers\SettingsImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename)
                    );
                    $text .= '     — <info>Theme files “'.$installRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                }
            }

            /*
             * Create default translation
             */
            if (!$this->hasDefaultTranslation()) {
                $defaultTrans = new Translation();
                $defaultTrans
                    ->setDefaultTranslation(true)
                    ->setLocale("en")
                    ->setName("Default translation");

                Kernel::getService('em')->persist($defaultTrans);
                Kernel::getService('em')->flush();

                $text .= '<info>Default translation installed…</info>'.PHP_EOL;
            } else {
                $text .= '<error>A default translation is already installed.</error>'.PHP_EOL;
            }

            /*
             * Install theme
             */
            if ($input->getOption('with-theme')) {
                $themeFile = $input->getOption('with-theme');
                $themeFile = str_replace('\\', '/', $themeFile);
                $themeFile = str_replace('Themes', 'themes', $themeFile);
                $themeFile .= ".php";

                if (file_exists($themeFile)) {
                    $fixtures = new Fixtures();
                    $fixtures->installFrontendTheme($input->getOption('with-theme'));
                    $text .= '<info>Theme class “'.$themeFile.'” has been installed…</info>'.PHP_EOL;

                    // install fixtures
                    $array = explode('\\', $input->getOption('with-theme'));
                    $themeRoot = ROADIZ_ROOT . "/themes/". $array[count($array) - 2];
                    $data = json_decode(file_get_contents($themeRoot . "/config.json"), true);

                    if (false !== $data && isset($data["importFiles"])) {
                        if (isset($data["importFiles"]['roles'])) {
                            foreach ($data["importFiles"]['roles'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\RolesImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme file “'.$themeRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                            }
                        }
                        if (isset($data["importFiles"]['groups'])) {
                            foreach ($data["importFiles"]['groups'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\GroupsImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme file “'.$themeRoot . "/" .$filename.'” has been imported..</info>'.PHP_EOL;
                            }
                        }
                        if (isset($data["importFiles"]['settings'])) {
                            foreach ($data["importFiles"]['settings'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\SettingsImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme files “'.$themeRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                            }
                        }
                        if (isset($data["importFiles"]['nodetypes'])) {
                            foreach ($data["importFiles"]['nodetypes'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\NodeTypesImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme file “'.$themeRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                            }

                            static::rebuildEntityManager();
                            SchemaCommand::updateSchema();
                        }
                        if (isset($data["importFiles"]['tags'])) {
                            foreach ($data["importFiles"]['tags'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\TagsImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme file “'.$themeRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                            }
                        }
                        if (isset($data["importFiles"]['nodes'])) {
                            foreach ($data["importFiles"]['nodes'] as $filename) {
                                \RZ\Roadiz\CMS\Importers\NodesImporter::importJsonFile(
                                    file_get_contents($themeRoot . "/" . $filename)
                                );
                                $text .= '     — <info>Theme file “'.$themeRoot . "/" .$filename.'” has been imported.</info>'.PHP_EOL;
                            }
                        }

                        SchemaCommand::updateSchema();
                    } else {
                        $text .= '<info>Theme class “'.$themeFile.'” has no data to import.</info>'.PHP_EOL;
                    }

                } else {
                    $text .= '<error>Theme class “'.$themeFile.'” does not exist.</error>'.PHP_EOL;
                }
            }

            $configuration = new YamlConfiguration();
            if (false === $configuration->load()) {
                $configuration->setConfiguration($configuration->getDefaultConfiguration());
            }
            $configuration->setInstall(false);
            $configuration->writeConfiguration();

            // Clear result cache
            $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }

            $text .= 'Install mode has been changed to false.'.PHP_EOL;
        }

        $output->writeln($text);
    }

    public static function rebuildEntityManager()
    {
        unset(Kernel::getInstance()->container["em.config"]);
        unset(Kernel::getInstance()->container["em"]);
        Kernel::getInstance()->container->register(new DoctrineServiceProvider());
    }

    private function hasDefaultBackend()
    {
        $default = Kernel::getService('em')
            ->getRepository("RZ\Roadiz\Core\Entities\Theme")
            ->findOneBy(["backendTheme"=>true]);

        return $default !== null ? true : false;
    }

    /**
     * Tell if there is any translation.
     *
     * @return boolean
     */
    public function hasDefaultTranslation()
    {
        $default = Kernel::getService('em')
            ->getRepository("RZ\Roadiz\Core\Entities\Translation")
            ->findOneBy([]);

        return $default !== null ? true : false;
    }
}
