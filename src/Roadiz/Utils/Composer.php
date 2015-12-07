<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file Composer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils;

use Symfony\Component\Filesystem\Filesystem;

/**
 *
 */
class Composer
{
    public static function postUpdate()
    {
        static::copyDefaultConfiguration();
        static::copyDevEnvironment();
    }

    public static function postInstall()
    {
        static::copyDefaultConfiguration();
        static::copyInstallEnvironment();
        static::copyDevEnvironment();
    }

    public static function copyDefaultConfiguration()
    {
        $fs = new Filesystem();
        $configFile = 'conf/config.yml';
        $configFileSrc = 'conf/config.default.yml';

        if (!$fs->exists($configFile) &&
            $fs->exists($configFileSrc)) {
            $fs->copy($configFileSrc, $configFile);
        }
    }

    public static function copyInstallEnvironment()
    {
        $fs = new Filesystem();
        $installFile = 'install.php';
        $installFileSrc = 'samples/install.php.sample';

        if (!$fs->exists($installFile) &&
            $fs->exists($installFileSrc)) {
            $fs->copy($installFileSrc, $installFile);
        }
    }

    public static function copyDevEnvironment()
    {
        $fs = new Filesystem();
        $devFile = 'dev.php';
        $devFileSrc = 'samples/dev.php.sample';

        if (!$fs->exists($devFile) &&
            $fs->exists($devFileSrc)) {
            $fs->copy($devFileSrc, $devFile);
        }
    }
}
