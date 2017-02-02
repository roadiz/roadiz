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

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;
use Themes\Install\InstallApp;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class InstallCommand extends Command
{
    /** @var EntityManager */
    private $entityManager;

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install Roadiz roles, settings, translations and default backend theme');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";

        $question = new ConfirmationQuestion(
            'Before installing Roadiz, did you create database schema? ' . PHP_EOL .
            'If not execute: <info>bin/roadiz orm:schema-tool:create</info>' . PHP_EOL .
            '<question>Are you sure to perform installation?</question> [y/N]: ',
            false
        );

        if ($input->getOption('no-interaction') ||
            $helper->ask($input, $output, $question)
        ) {
            /*
             * Create backend theme
             */
            if (!$this->hasDefaultBackend()) {
                $theme = new Theme();
                $theme->setAvailable(true)
                    ->setBackendTheme(true)
                    ->setClassName('Themes\Rozier\RozierApp');

                $this->entityManager->persist($theme);
                $this->entityManager->flush();

                $text .= '<info>Rozier back-end theme installed…</info>' . PHP_EOL;
            } else {
                $text .= '<error>A back-end theme is already installed.</error>' . PHP_EOL;
            }

            /**
             * Import default data
             */
            $installRoot = InstallApp::getThemeFolder();
            $data = Yaml::parse($installRoot . "/config.yml");

            if (isset($data["importFiles"]['roles'])) {
                foreach ($data["importFiles"]['roles'] as $filename) {
                    RolesImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $installRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['groups'])) {
                foreach ($data["importFiles"]['groups'] as $filename) {
                    GroupsImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme file “' . $installRoot . "/" . $filename . '” has been imported..</info>' . PHP_EOL;
                }
            }
            if (isset($data["importFiles"]['settings'])) {
                foreach ($data["importFiles"]['settings'] as $filename) {
                    SettingsImporter::importJsonFile(
                        file_get_contents($installRoot . "/" . $filename),
                        $this->entityManager
                    );
                    $text .= '     — <info>Theme files “' . $installRoot . "/" . $filename . '” has been imported.</info>' . PHP_EOL;
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

                $this->entityManager->persist($defaultTrans);
                $this->entityManager->flush();

                $text .= '<info>Default translation installed…</info>' . PHP_EOL;
            } else {
                $text .= '<error>A default translation is already installed.</error>' . PHP_EOL;
            }

            // Clear result cache
            $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }
        }

        $output->writeln($text);
    }

    private function hasDefaultBackend()
    {
        $default = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findOneBy(["backendTheme" => true]);

        return $default !== null ? true : false;
    }

    /**
     * Tell if there is any translation.
     *
     * @return boolean
     */
    public function hasDefaultTranslation()
    {
        $default = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findOneBy([]);

        return $default !== null ? true : false;
    }
}
