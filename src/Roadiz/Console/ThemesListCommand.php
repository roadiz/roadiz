<?php
declare(strict_types=1);

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
