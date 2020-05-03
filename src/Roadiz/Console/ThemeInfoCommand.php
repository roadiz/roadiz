<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Utils\Theme\ThemeInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ThemeInfoCommand extends Command
{
    protected function configure()
    {
        $this->setName('themes:info')
            ->setDescription('Get information from a Theme.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (without the "Theme" suffix) or full-qualified ThemeApp class name (you can use / instead of \\).'
            )
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
        $name = str_replace('/', '\\', $input->getArgument('name'));
        $themeInfo = new ThemeInfo($name, $this->getHelper('kernel')->getKernel()->getProjectDir());

        if ($themeInfo->exists()) {
            if (!$themeInfo->isValid()) {
                throw new InvalidArgumentException($themeInfo->getClassname() . ' is not a valid theme.');
            }
            $io->table([
                'Description', 'Value'
            ], [
                ['Given name', $themeInfo->getName()],
                ['Theme classname', $themeInfo->getClassname()],
                ['Theme path', $themeInfo->getThemePath()],
                ['Assets path', $themeInfo->getThemePath().'/static'],
            ]);
            return 0;
        }
        throw new InvalidArgumentException($themeInfo->getClassname() . ' does not exist.');
    }
}
