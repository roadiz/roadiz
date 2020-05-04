<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeMigrateCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('themes:migrate')
            ->setDescription('Update your site against theme import files, regenerate NSEntities, update database schema and clear caches.')
            ->addArgument(
                'classname',
                InputArgument::REQUIRED,
                'Main theme classname (Use / instead of \\ and do not forget starting slash)'
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
        $io = new SymfonyStyle($input, $output);
        $question = new ConfirmationQuestion('<question>Are you sure to migrate against this theme?</question> This can lead in data loss.', false);
        if ($io->askQuestion($question) === false) {
            $io->note('Nothing was done…');
            return 0;
        }

        if ($input->getOption('dry-run')) {
            $this->runCommand(sprintf('themes:install --data "%s" --dry-run -v', $input->getArgument('classname')));
        } else {
            $this->runCommand(sprintf('themes:install --data "%s" -v', $input->getArgument('classname')));
            $this->runCommand(sprintf('generate:nsentities -v'));
            $this->runCommand(sprintf('orm:schema-tool:update --dump-sql --force -v'), 'dev', false);
            $this->runCommand(sprintf('cache:clear -v'), 'dev', false);
            $this->runCommand(sprintf('cache:clear -v'), 'dev', true);
            $this->runCommand(sprintf('cache:clear -v'), 'prod', false);
            $this->runCommand(sprintf('cache:clear -v'), 'prod', true);
            $this->runCommand(sprintf('cache:clear-fpm -v'), 'prod', false);
            $this->runCommand(sprintf('cache:clear-fpm -v'), 'prod', true);
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
