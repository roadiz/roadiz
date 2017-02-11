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
 * @file Composer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Composer
 * @package RZ\Roadiz\Utils
 */
class Composer
{
    /**
     * Occurs after the update command has been executed,
     * or after the install command has been executed without a lock file present.
     */
    public static function postUpdate()
    {
        static::copyDefaultConfiguration();
        static::copyProductionEnvironment();
        static::copyInstallEnvironment();
        static::copyDevEnvironment();
        static::copyClearCacheEntryPoint();
    }

    /**
     * Occurs after the install command has been executed with a lock file present.
     */
    public static function postInstall()
    {
        static::copyDefaultConfiguration();
        static::copyProductionEnvironment();
        static::copyInstallEnvironment();
        static::copyDevEnvironment();
        static::copyClearCacheEntryPoint();
    }
    /**
     * @return Kernel
     */
    public static function getKernel()
    {
        return new Kernel('prod', false);
    }

    public static function copyDefaultConfiguration()
    {
        $kernel = static::getKernel();
        $fs = new Filesystem();
        $configFile = $kernel->getRootDir() . '/conf/config.yml';
        $configFileSrc = $kernel->getRootDir() . '/conf/config.default.yml';

        if (!$fs->exists($configFile) &&
            $fs->exists($configFileSrc)) {
            $fs->copy($configFileSrc, $configFile);
            echo 'Copying conf/config.yml default configuration.' . PHP_EOL;
        }
    }

    public static function copyProductionEnvironment()
    {
        $kernel = static::getKernel();
        $fs = new Filesystem();
        $indexFile = $kernel->getPublicDir() . '/index.php';
        $indexFileSrc = 'samples/index.php.sample';

        if (!$fs->exists($indexFile) &&
            $fs->exists($indexFileSrc)) {
            $fs->copy($indexFileSrc, $indexFile);
            echo 'Copying index.php entry point.' . PHP_EOL;
        }
    }

    public static function copyInstallEnvironment()
    {
        $kernel = static::getKernel();
        $fs = new Filesystem();
        $installFile = $kernel->getPublicDir() . '/install.php';
        $installFileSrc = 'samples/install.php.sample';

        if (!$fs->exists($installFile) &&
            $fs->exists($installFileSrc)) {
            $fs->copy($installFileSrc, $installFile);
            echo 'Copying install.php entry point.' . PHP_EOL;
        }
    }

    public static function copyDevEnvironment()
    {
        $kernel = static::getKernel();
        $fs = new Filesystem();
        $devFile = $kernel->getPublicDir() . '/dev.php';
        $devFileSrc = 'samples/dev.php.sample';

        if (!$fs->exists($devFile) &&
            $fs->exists($devFileSrc)) {
            $fs->copy($devFileSrc, $devFile);
            echo 'Copying dev.php entry point.' . PHP_EOL;
        }
    }

    public static function copyClearCacheEntryPoint()
    {
        $kernel = static::getKernel();
        $fs = new Filesystem();
        $clearCacheFile = $kernel->getPublicDir() . '/clear_cache.php';
        $clearCacheFileSrc = 'samples/clear_cache.php.sample';

        if (!$fs->exists($clearCacheFile) &&
            $fs->exists($clearCacheFileSrc)) {
            $fs->copy($clearCacheFileSrc, $clearCacheFile);
            echo 'Copying clear_cache.php entry point.' . PHP_EOL;
        }
    }
}
