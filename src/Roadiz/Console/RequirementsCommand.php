<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Console\Tools\Requirements;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for testing requirements from terminal.
 */
class RequirementsCommand extends Command
{
    /** @var Requirements */
    private $requirements;

    /**
     * @inheritDoc
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('requirements')
            ->setDescription('Test server requirements.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $this->requirements = new Requirements($kernel);

        $io->note('Please note that following values are extracted from your CLI environment, they can differ from your Web environment.');

        $io->listing([
            $this->testPHPVersion('5.6.0'),
            $this->testExtension('session'),
            $this->testExtension('json'),
            $this->testExtension('zip'),
            $this->testExtension('date'),
            $this->testExtension('gd'),
            $this->testExtension('curl'),
            $this->testExtension('intl'),
            $this->testPHPIntValue('memory_limit', '64M'),
            $this->testPHPIntValue('post_max_size', '16M'),
            $this->testPHPIntValue('upload_max_filesize', '16M'),
            $this->methodExists('gettext'),
            $this->folderWritable($kernel->getRootDir()),
            $this->folderWritable($kernel->getRootDir() . '/conf'),
            $this->folderWritable($kernel->getCacheDir()),
            $this->folderWritable($kernel->getPublicFilesPath()),
            $this->folderWritable($kernel->getPrivateFilesPath()),
            $this->folderWritable($kernel->getFontsFilesPath()),
            $this->folderWritable($kernel->getRootDir() . '/gen-src'),
        ]);
        return 0;
    }

    protected function testPHPIntValue($name, $expected): string
    {
        $actual = ini_get($name);
        $actualM = $this->requirements->parseSuffixedAmount($actual);

        $expectedM = $this->requirements->parseSuffixedAmount($expected);

        if (!$this->requirements->testPHPIntValue($name, $expected)) {
            return '<info>' . $name . '</info> : ' . $actualM . 'M  Excepted : ' . $expectedM . 'M — <error>Fail</error>';
        } else {
            return '<info>' . $name . '</info> : ' . $actualM . 'M — Excepted : ' . $expectedM . 'M ';
        }
    }

    protected function methodExists($name, $mandatory = true): string
    {
        if ($this->requirements->methodExists($name) && $mandatory === true) {
            return '<info>Method ' . $name . '()</info> — OK';
        } else {
            return '<info>Method ' . $name . '()</info> — <error>Fail</error>';
        }
    }

    protected function folderWritable($filename): string
    {
        if ($this->requirements->folderWritable($filename)) {
            return '<info>Folder “' . $filename . '”</info> — Writable';
        } else {
            return '<info>Folder “' . $filename . '”</info> — <error>Not writable</error>';
        }
    }

    protected function testExtension($name): string
    {
        if (!extension_loaded($name)) {
            return '<info>Extension ' . $name . '</info> is not installed — <error>Fail</error>';
        } else {
            return '<info>Extension ' . $name . '</info> is installed — OK';
        }
    }

    protected function testPHPVersion($version): string
    {
        if (version_compare(phpversion(), $version, '<')) {
            return '<info>PHP</info> version is too old — <error>Fail</error>';
        } else {
            return '<info>PHP</info> version (v' . phpversion() . ') — OK';
        }
    }
}
