<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Theme\ThemeGenerator;
use RZ\Roadiz\Utils\Theme\ThemeInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeGenerateCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('themes:generate')
            ->setDescription('Generate a new theme based on BaseTheme boilerplate. <info>Requires "find", "sed" and "git" commands.</info>')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (without the "Theme" suffix)'
            )
            ->addOption(
                'develop',
                'd',
                InputOption::VALUE_NONE,
                'Use BaseTheme develop branch instead of master.'
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Choose BaseTheme branch.'
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the theme assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $branch = 'master';
        if ($input->getOption('develop')) {
            $branch = 'develop';
        }
        if ($input->getOption('branch')) {
            $branch = $input->getOption('branch');
        }
        if ($input->getOption('relative')) {
            $expectedMethod = ThemeGenerator::METHOD_RELATIVE_SYMLINK;
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = ThemeGenerator::METHOD_ABSOLUTE_SYMLINK;
        } else {
            $expectedMethod = ThemeGenerator::METHOD_COPY;
        }

        $name = str_replace('/', '\\', $input->getArgument('name'));
        $themeInfo = new ThemeInfo($name, $this->getHelper('kernel')->getKernel()->getProjectDir());
        /** @var ThemeGenerator $themeGenerator */
        $themeGenerator = $this->get(ThemeGenerator::class);

        if ($io->confirm(
            'Are you sure you want to generate a new theme called: "' . $themeInfo->getThemeName() . '"' .
            ' using ' . $branch . ' branch and installing its assets with ' . $expectedMethod . ' method?',
            false
        )) {
            if (!$themeInfo->exists()) {
                $themeGenerator->downloadTheme($themeInfo, $branch);
                $io->success('BaseTheme cloned into ' . $themeInfo->getThemePath());
            }

            $themeGenerator->renameTheme($themeInfo);

            $themeGenerator->installThemeAssets($themeInfo, $expectedMethod);

            $this->runCommand(sprintf('themes:register "%s" -v', $themeInfo->getClassname()));

            $io->success($themeInfo->getThemeName() . ' has been regenerated and is ready to be installed, have fun!');
        }

        return 0;
    }

    /**
     * @param string $command
     * @param string $environment
     * @param bool   $preview
     *
     * @return int
     */
    protected function runCommand(string $command, $environment = 'dev', $preview = false)
    {
        /** @var Kernel $existingKernel */
        $existingKernel = $this->getHelper('kernel')->getKernel();
        $process = Process::fromShellCommandline(
            'php bin/roadiz ' . $command . ' -e ' . $environment . ($preview ? ' --preview' : '')
        );
        $process->setWorkingDirectory($existingKernel->getProjectDir());
        $process->setTty(true);
        $process->run();
        return $process->wait();
    }
}
