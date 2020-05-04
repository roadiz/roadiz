<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Utils\Theme\ThemeGenerator;
use RZ\Roadiz\Utils\Theme\ThemeInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ThemeAssetsCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('themes:assets:install')
            ->setDescription('Install a theme assets folder in public directory.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (without the "Theme" suffix) or full-qualified ThemeApp class name (you can use / instead of \\).'
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the theme assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('relative')) {
            $expectedMethod = ThemeGenerator::METHOD_RELATIVE_SYMLINK;
            $io->writeln('Trying to install theme assets as <info>relative symbolic link</info>.');
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = ThemeGenerator::METHOD_ABSOLUTE_SYMLINK;
            $io->writeln('Trying to install theme assets as <info>absolute symbolic link</info>.');
        } else {
            $expectedMethod = ThemeGenerator::METHOD_COPY;
            $io->writeln('Installing theme assets as <info>hard copy</info>.');
        }
        $name = str_replace('/', '\\', $input->getArgument('name'));

        $themeInfo = new ThemeInfo($name, $this->getHelper('kernel')->getKernel()->getProjectDir());
        /** @var ThemeGenerator $themeGenerator */
        $themeGenerator = $this->get(ThemeGenerator::class);

        if ($themeInfo->exists()) {
            $io->table([
                'Description', 'Value'
            ], [
                ['Given name', $themeInfo->getName()],
                ['Theme path', $themeInfo->getThemePath()],
                ['Assets path', $themeInfo->getThemePath().'/static'],
            ]);

            $themeGenerator->installThemeAssets($themeInfo, $expectedMethod);
            return 0;
        }
        throw new InvalidArgumentException($themeInfo->getThemePath() . ' does not exist.');
    }
}
