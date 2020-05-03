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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ThemeRegisterCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('themes:register')
            ->setDescription('Register a theme into Roadiz configuration.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (with or without the "Theme" suffix) or full-qualified ThemeApp class name (you can use / instead of \\).'
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
        /** @var ThemeGenerator $themeGenerator */
        $themeGenerator = $this->get(ThemeGenerator::class);

        if ($themeInfo->exists()) {
            $themeGenerator->registerTheme($themeInfo);
            $io->success($themeInfo->getThemePath() . ' has been registered.');
            return 0;
        }
        throw new InvalidArgumentException($themeInfo->getClassname() . ' does not exist.');
    }
}
