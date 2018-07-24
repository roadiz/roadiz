<?php
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
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $this->requirements = new Requirements($kernel);
        $text = "";

        $text .= $this->testPHPVersion('5.6.0');
        $text .= $this->testExtension('session');
        $text .= $this->testExtension('json');
        $text .= $this->testExtension('zip');
        $text .= $this->testExtension('date');
        $text .= $this->testExtension('gd');
        $text .= $this->testExtension('curl');
        $text .= $this->testExtension('intl');

        $text .= $this->testPHPIntValue('memory_limit', '64M');
        $text .= $this->testPHPIntValue('post_max_size', '16M');
        $text .= $this->testPHPIntValue('upload_max_filesize', '16M');

        $text .= $this->methodExists('gettext');
        $text .= $this->folderWritable($kernel->getRootDir());
        $text .= $this->folderWritable($kernel->getRootDir() . '/conf');
        $text .= $this->folderWritable($kernel->getCacheDir());
        $text .= $this->folderWritable($kernel->getPublicFilesPath());
        $text .= $this->folderWritable($kernel->getPrivateFilesPath());
        $text .= $this->folderWritable($kernel->getFontsFilesPath());
        $text .= $this->folderWritable($kernel->getRootDir() . '/gen-src');

        $output->writeln($text);
    }

    protected function testPHPIntValue($name, $expected)
    {
        $actual = ini_get($name);
        $actualM = $this->requirements->parseSuffixedAmount($actual);

        $expectedM = $this->requirements->parseSuffixedAmount($expected);

        if (!$this->requirements->testPHPIntValue($name, $expected)) {
            return '<info>' . $name . '</info> : ' . $actualM . 'M  Excepted : ' . $expectedM . 'M — <error>Fail</error>' . PHP_EOL;
        } else {
            return '<info>' . $name . '</info> : ' . $actualM . 'M — Excepted : ' . $expectedM . 'M ' . PHP_EOL;
        }
    }

    protected function methodExists($name, $mandatory = true)
    {
        if ($this->requirements->methodExists($name) && $mandatory === true) {
            return '<info>Method ' . $name . '()</info> — OK' . PHP_EOL;
        } else {
            return '<info>Method ' . $name . '()</info> — <error>Fail</error>' . PHP_EOL;
        }
    }

    protected function folderWritable($filename)
    {
        if ($this->requirements->folderWritable($filename)) {
            return '<info>Folder “' . $filename . '”</info> — Writable' . PHP_EOL;
        } else {
            return '<info>Folder “' . $filename . '”</info> — <error>Not writable</error>' . PHP_EOL;
        }
    }

    protected function testExtension($name)
    {
        if (!extension_loaded($name)) {
            return '<info>Extension ' . $name . '</info> is not installed — <error>Fail</error>' . PHP_EOL;
        } else {
            return '<info>Extension ' . $name . '</info> is installed — OK' . PHP_EOL;
        }
    }

    protected function testPHPVersion($version)
    {
        if (version_compare(phpversion(), $version, '<')) {
            return '<info>PHP</info> version is too old — <error>Fail</error>' . PHP_EOL;
        } else {
            return '<info>PHP</info> version (v' . phpversion() . ') — OK' . PHP_EOL;
        }
    }
}
