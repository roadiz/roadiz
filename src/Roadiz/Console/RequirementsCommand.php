<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file RequirementsCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for testing requirements from terminal.
 */
class RequirementsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('requirements')
            ->setDescription('Test server requirements.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        $text .= $this->testPHPVersion('5.4');
        $text .= $this->testExtension('intl');
        $text .= $this->testExtension('ereg');
        $text .= $this->testExtension('session');
        $text .= $this->testExtension('json');
        $text .= $this->testExtension('zip');
        $text .= $this->testExtension('date');
        $text .= $this->testExtension('gd');
        $text .= $this->testExtension('imap');
        $text .= $this->testExtension('curl');

        $text .= $this->testPHPIntValue('memory_limit', '64');
        $text .= $this->testPHPIntValue('post_max_size', '16');
        $text .= $this->testPHPIntValue('upload_max_filesize', '16');

        $text .= $this->methodExists('gettext');
        $text .= $this->folderWritable(ROADIZ_ROOT);

        $output->writeln($text);
    }


    protected function testPHPIntValue($name, $expected)
    {
        $intValue = (int) (str_replace(array('s','K','M','G'), array('','','',''), ini_get($name)));

        if ($intValue < $expected) {
            return '<info>'.$name.'</info> : '.ini_get($name).'  Excepted : '.$expected.' — <error>Fail</error>'.PHP_EOL;
        }

        return '<info>'.$name.'</info> : '.ini_get($name).' — Excepted : '.$expected.''.PHP_EOL;
    }

    protected function methodExists($name, $mandatory = true)
    {
        return '<info>Method '.$name.'()</info> — '.(function_exists($name) === true && $mandatory === true ? 'OK' : '<error>Fail</error>').''.PHP_EOL;
    }

    protected function folderWritable($filename)
    {
        return '<info>Folder “'.$filename.'”</info> — '.(is_writable($filename) === true ? 'Writable' : '<error>Not writable</error>').''.PHP_EOL;
    }

    protected function testExtension($name)
    {
        if (!extension_loaded($name)) {
            return '<info>Extension '.$name.'</info> is not installed — <error>Fail</error>'.PHP_EOL;
        } else {
            return '<info>Extension '.$name.'</info> is installed — OK'.PHP_EOL;
        }
    }

    protected function testPHPVersion($version)
    {
        if (version_compare(phpversion(), $version, '<')) {
            return '<info>PHP</info> version is too old — <error>Fail</error>'.PHP_EOL;
        } else {
            return '<info>PHP</info> version (v'.phpversion().') — OK'.PHP_EOL;
        }
    }
}
