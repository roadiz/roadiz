<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file Requirements.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console\Tools;

use RZ\Roadiz\Core\Kernel;

/**
 * Requirements class
 */
class Requirements
{
    protected $totalChecks = 0;
    protected $successChecks = 0;
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * Requirements constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return boolean
     */
    public function isTotalSuccess()
    {
        return $this->totalChecks <= $this->successChecks;
    }

    /**
     * @return array $checks
     */
    public function getRequirements()
    {
        $checks = [];

        $checks['php_version'] = [
            'status' => $this->testPHPVersion('5.6.0'),
            'version_minimum' => '5.6.0',
            'found' => phpversion(),
            'message' => 'Your PHP version is outdated, you must update it.',
        ];
        if ($checks['php_version']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['session'] = [
            'status' => $this->testExtension('session'),
            'extension' => true,
            'message' => 'You must enable PHP sessions.',
        ];
        if ($checks['session']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['json'] = [
            'status' => $this->testExtension('json'),
            'extension' => true,
            'message' => 'JSON library is needed for configuration handling.',
        ];

        if ($checks['json']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['zip'] = [
            'status' => $this->testExtension('zip'),
            'extension' => true,
            'message' => 'ZIP extension is needed.',
        ];

        if ($checks['zip']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['date'] = [
            'status' => $this->testExtension('date'),
            'extension' => true,
            'message' => 'Date extension is needed.',
        ];

        if ($checks['date']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['gd'] = [
            'status' => $this->testExtension('gd'),
            'extension' => true,
            'message' => 'GD library must be installed.',
        ];

        if ($checks['gd']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['fileinfo'] = [
            'status' => $this->testExtension('fileinfo'),
            'extension' => true,
            'message' => 'Fileinfo extension must be installed.',
        ];

        if ($checks['fileinfo']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['curl'] = [
            'status' => $this->testExtension('curl'),
            'extension' => true,
            'message' => 'cUrl extension is needed for API requests.',
        ];

        if ($checks['curl']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['intl'] = [
            'status' => $this->testExtension('intl'),
            'extension' => true,
            'message' => 'Intl extension is needed for translations.',
        ];
        if ($checks['intl']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['memory_limit'] = [
            'status' => $this->testPHPIntValue('memory_limit', '64M'),
            'value_minimum' => '64M',
            'found' => ini_get('memory_limit'),
            'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”',
        ];

        if ($checks['memory_limit']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['post_max_size'] = [
            'status' => $this->testPHPIntValue('post_max_size', '16M'),
            'value_minimum' => '16M',
            'found' => ini_get('post_max_size'),
            'message' => 'Your PHP configuration has a too low value for “post_max_size”',
        ];

        if ($checks['post_max_size']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['upload_max_filesize'] = [
            'status' => $this->testPHPIntValue('upload_max_filesize', '16M'),
            'value_minimum' => '16M',
            'found' => ini_get('upload_max_filesize'),
            'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”',
        ];

        if ($checks['upload_max_filesize']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        $checks['files_folder_writable'] = [
            'status' => $this->folderWritable($this->kernel->getPublicFilesPath()),
            'folder' => $this->kernel->getPublicFilesPath(),
            'mod' => fileperms($this->kernel->getPublicFilesPath()),
            'message' => 'Storage folder is not writable by PHP, you must change its permissions.',
        ];

        if ($checks['files_folder_writable']['status']) {
            $this->successChecks++;
        }
        $this->totalChecks++;

        return $checks;
    }

    /**
     * @param string  $name
     * @param integer $expected
     *
     * @return boolean
     */
    public function testPHPIntValue($name, $expected)
    {
        $expected = $this->parseSuffixedAmount($expected);
        $actual = $this->parseSuffixedAmount(ini_get($name));

        /*
         * 0 value means no limitations
         */
        if ($actual === 0 || $actual == -1) {
            return true;
        } elseif ($actual < $expected) {
            return false;
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function methodExists($name)
    {
        return (function_exists($name) === true) ? (true) : (false);
    }

    /**
     * @param string $filename
     *
     * @return boolean
     */
    public function folderWritable($filename)
    {
        return is_writable($filename) === true ? true : false;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function testExtension($name)
    {
        return extension_loaded($name);
    }

    /**
     * @param integer $version
     *
     * @return integer
     */
    public function testPHPVersion($version)
    {
        return !version_compare(phpversion(), $version, '<');
    }

    /**
     * @param string $amount
     * @return int Always return value in Megas
     */
    public function parseSuffixedAmount($amount)
    {
        $intValue = intval(preg_replace('#([0-9]+)[s|k|m|g|t]#i', '$1', $amount));

        /*
         * If actual is in Gigas
         */
        if (preg_match('#([0-9]+)g#i', $amount) > 0) {
            return $intValue * 1024;
        } elseif (preg_match('#([0-9]+)t#i', $amount) > 0) {
            return $intValue * 1024 * 1024;
        } elseif (preg_match('#([0-9]+)k#i', $amount) > 0) {
            return $intValue / 1024;
        } else {
            return $intValue;
        }
    }
}
