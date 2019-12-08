<?php
declare(strict_types=1);
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file RequirementsCommand.php
 * @author Ambroise Maupate
 */
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
