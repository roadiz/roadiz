<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ThemesCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemesCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('themes')
            ->setDescription('Manage themes')
            ->addArgument(
                'classname',
                InputArgument::OPTIONAL,
                'Main theme classname'
            )
            ->addOption(
                'setup',
                null,
                InputOption::VALUE_NONE,
                'Setup theme'
            )
            ->addOption(
                'disable',
                null,
                InputOption::VALUE_NONE,
                'Disable theme'
            )
            ->addOption(
                'force-twig-compilation',
                null,
                InputOption::VALUE_NONE,
                'Force Twig templates compilation'
            )
            ->addOption(
                'enable',
                null,
                InputOption::VALUE_NONE,
                'Enable theme'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";
        $name = $input->getArgument('classname');

        if ($name) {

            $theme = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Theme')
                ->findOneBy(array('className'=>$name));

            if ($theme !== null) {

                if ($input->getOption('enable')) {
                    if ($theme !== null && $name::enable()) {
                        $text = '<info>Theme enabled…</info>'.PHP_EOL;
                    } else {
                        $text = '<error>Requested theme is not setup yet…</error>'.PHP_EOL;
                    }
                }
                if ($input->getOption('disable')) {
                    if ($theme !== null && $name::disable()) {
                        $text = '<info>Theme disabled…</info>'.PHP_EOL;
                    } else {
                        $text = '<error>Requested theme is not setup yet…</error>'.PHP_EOL;
                    }
                }

                if ($input->getOption('force-twig-compilation')) {
                    if (true === $name::forceTwigCompilation()) {
                        $text = '<info>Twig templates have been compiled…</info>'.PHP_EOL;
                    }
                }
            } else {
                if ($name::setup() === true) {
                    $text = '<info>Theme setup sucessfully…</info>'.PHP_EOL;
                } else {
                    $text = '<error>Cannot setup theme…</error>'.PHP_EOL;
                }
            }
        } else {
            $text = '<info>Installed theme…</info>'.PHP_EOL;
            $themes = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Theme')
                ->findAll();

            if (count($themes) > 0) {
                $text .= ' | '.PHP_EOL;
                foreach ($themes as $theme) {
                    $text .=
                        ' |_ '.$theme->getClassName()
                        .' — <info>'.($theme->isAvailable()?'enabled':'disabled').'</info>'
                        .' — <comment>'.($theme->isBackendTheme()?'Backend':'Frontend').'</comment>'
                        .PHP_EOL;
                }
            } else {
                $text = '<info>No available themes</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}