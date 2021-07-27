<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Themes\Install\InstallApp;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class InstallCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install Roadiz roles, settings, translations and default backend theme');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getHelper('doctrine')->getManagerRegistry();

        $io->note('Before installing Roadiz, did you create database schema? ' . PHP_EOL .
            'If not execute: bin/roadiz orm:schema-tool:create');
        $question = new ConfirmationQuestion(
            '<question>Are you sure to perform installation?</question>',
            false
        );

        if ($input->getOption('no-interaction') ||
            $io->askQuestion($question)
        ) {
            /**
             * Import default data
             */
            $installRoot = InstallApp::getThemeFolder();
            $data = Yaml::parse(file_get_contents($installRoot . "/config.yml"));

            if (isset($data["importFiles"]['roles'])) {
                foreach ($data["importFiles"]['roles'] as $filename) {
                    $this->get(RolesImporter::class)->import(file_get_contents($installRoot . "/" . $filename));
                    $io->success('Theme file “' . $installRoot . "/" . $filename . '” has been imported.');
                }
            }
            if (isset($data["importFiles"]['groups'])) {
                foreach ($data["importFiles"]['groups'] as $filename) {
                    $this->get(GroupsImporter::class)->import(file_get_contents($installRoot . "/" . $filename));
                    $io->success('Theme file “' . $installRoot . "/" . $filename . '” has been imported.');
                }
            }
            if (isset($data["importFiles"]['settings'])) {
                foreach ($data["importFiles"]['settings'] as $filename) {
                    $this->get(SettingsImporter::class)->import(file_get_contents($installRoot . "/" . $filename));
                    $io->success('Theme files “' . $installRoot . "/" . $filename . '” has been imported.');
                }
            }
            /** @var ObjectManager $manager */
            $manager = $managerRegistry->getManagerForClass(Translation::class);
            /*
             * Create default translation
             */
            if (!$this->hasDefaultTranslation()) {
                $defaultTrans = new Translation();
                $defaultTrans
                    ->setDefaultTranslation(true)
                    ->setLocale("en")
                    ->setName("Default translation");

                $manager->persist($defaultTrans);

                $io->success('Default translation installed.');
            } else {
                $io->warning('A default translation is already installed.');
            }
            $manager->flush();

            if ($manager instanceof EntityManagerInterface) {
                // Clear result cache
                /** @var CacheProvider $cacheDriver */
                $cacheDriver = $manager->getConfiguration()->getResultCacheImpl();
                if ($cacheDriver !== null) {
                    $cacheDriver->deleteAll();
                }
            }
        }
        return 0;
    }

    /**
     * Tell if there is any translation.
     *
     * @return boolean
     */
    public function hasDefaultTranslation()
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getHelper('doctrine')->getManagerRegistry();
        $default = $managerRegistry->getRepository(Translation::class)->findOneBy([]);

        return $default !== null;
    }
}
