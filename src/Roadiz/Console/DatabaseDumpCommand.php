<?php
/**
 * Copyright (c) 2017.
 *
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
 *
 * @file DatabaseDumpCommand.php
 * @author ambroisemaupate
 *
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DatabaseDumpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('database:dump')
            ->setDescription('Use system mysqldump to export your database contents to STDOUT.')
            ->setHelp(
                <<<'EOF'
Use <info>bin/roadiz database:dump > my-file.sql</info> command to generate a custom named .sql file.
Or <info>bin/roadiz database:dump -c</info> command to generate an automatically named .sql file in root dir.
<info>mysqldump</info> MUST be installed on your system (via mysql-client packages), this command uses system processes.
EOF
            )
            ->setDefinition([
                new InputOption('gzip', 'g', InputOption::VALUE_NONE, 'Compress file with gzip'),
                new InputOption('create-file', 'c', InputOption::VALUE_NONE, 'Let Roadiz create a dump file in root-dir with automatic naming for you.'),
            ])
        ;
    }

    /**
     * @param string $appName
     * @return string
     */
    protected function getDumpFileName($appName = "mysql_dump")
    {
        return $appName . '_' . date('Y-m-d') . '.sql';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getHelper('configuration')->getConfiguration();
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        $fileName = $this->getDumpFileName($configuration['appNamespace']);

        if ($configuration['doctrine']['driver'] === 'pdo_mysql') {
            $testProcessCmd = 'command -v mysqldump';
            $processArray = [
                'mysqldump',
                '-h' . $configuration['doctrine']['host'],
                '-u' . $configuration['doctrine']['user'],
                '-p' . $configuration['doctrine']['password'],
                $configuration['doctrine']['dbname']
            ];
        }

        if (isset($processArray) && isset($testProcessCmd)) {
            if ($input->getOption('gzip')) {
                $processArray = array_merge($processArray, [
                    '|',
                    'gzip'
                ]);

                $fileName .= '.gz';
            }

            /** @var Process $testProcess */
            $testProcess = $this->getHelper('process')->mustRun($output, $testProcessCmd);
            $testProcess->disableOutput();
            if ($testProcess->isSuccessful()) {
                /** @var Process $process */
                $process = $this->getHelper('process')->mustRun($output, implode(' ', $processArray));
                if ($process->isSuccessful()) {
                    if ($input->getOption('create-file')) {
                        $filePath = $kernel->getRootDir().'/'.$fileName;
                        $fs = new Filesystem();
                        $fs->dumpFile($filePath, $process->getOutput());
                        $fs->chmod($filePath, 0640);
                    } else {
                        $output->writeln($process->getOutput());
                    }
                    return 0;
                }
            }
            throw new \RuntimeException('SQL dump binary is not installed on your system.');
        }
        throw new \RuntimeException('database:dump command only supports MySQL');
    }
}
