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

        $question = new ConfirmationQuestion(
            '<question>Are you sure to migrate against this theme?</question> This can lead in data loss.',
            !$input->isInteractive()
        );
        if ($io->askQuestion($question) === false) {
            $io->note('Nothing was done…');
            return 0;
        }

        if ($input->getOption('dry-run')) {
            $this->runCommand(
                sprintf('themes:install --data "%s" --dry-run', $input->getArgument('classname')),
                'dev',
                false,
                $input->isInteractive()
            );
        } else {
            $this->runCommand(
                sprintf('migrations:migrate --allow-no-migration'),
                'dev',
                false,
                $input->isInteractive()
            );
            $this->runCommand(
                sprintf('themes:install --data "%s"', $input->getArgument('classname')),
                'dev',
                false,
                $input->isInteractive()
            );
            $this->runCommand(sprintf('generate:nsentities'), 'dev', false, $input->isInteractive());
            $this->runCommand(
                sprintf('orm:schema-tool:update --dump-sql --force'),
                'dev',
                false,
                $input->isInteractive()
            );
            $this->runCommand(sprintf('cache:clear'), 'dev', false, $input->isInteractive());
            $this->runCommand(sprintf('cache:clear'), 'dev', true, $input->isInteractive());
            $this->runCommand(sprintf('cache:clear'), 'prod', false, $input->isInteractive());
            $this->runCommand(sprintf('cache:clear'), 'prod', true, $input->isInteractive());
            $this->runCommand(sprintf('cache:clear-fpm'), 'prod', false, $input->isInteractive());
            $this->runCommand(sprintf('cache:clear-fpm'), 'prod', true, $input->isInteractive());
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
    protected function runCommand(string $command, string $environment = 'dev', bool $preview = false, bool $interactive = true)
    {
        /** @var Kernel $existingKernel */
        $existingKernel = $this->getHelper('kernel')->getKernel();
        $args = $interactive ? ' -v ' : ' -nq ';
        $process = Process::fromShellCommandline(
            'php bin/roadiz ' . $args . $command . ' --env ' . $environment . ($preview ? ' --preview' : '')
        );
        $process->setWorkingDirectory($existingKernel->getProjectDir());
        $process->setTty($interactive);
        $process->run();
        return $process->wait();
    }
}
