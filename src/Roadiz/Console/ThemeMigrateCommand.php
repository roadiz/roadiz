<?php
declare(strict_types=1);
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeMigrateCommand extends ThemesCommand implements ContainerAwareInterface
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
     * @throws \Exception
     */
    protected function runCommand(string $command, $environment = 'dev', $preview = false)
    {
        /** @var Kernel $existingKernel */
        $existingKernel = $this->getHelper('kernel')->getKernel();
        $process = new Process(
            'php bin/roadiz ' . $command . ' -e ' . $environment . ($preview ? ' --preview' : '')
        );
        $process->setWorkingDirectory($existingKernel->getProjectDir());
        $process->setTty(true);
        $process->run();
        $process->wait();
    }
}
