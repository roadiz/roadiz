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

use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\Event;

/**
 * Class Composer
 * @package RZ\Roadiz\Utils
 */
class Composer
{
    /**
     * Occurs after the update command has been executed,
     * or after the install command has been executed without a lock file present.
     *
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        static::copyDefaultConfiguration();
        static::copyInstallEnvironment();
        static::copyDevEnvironment();
        static::copyClearCacheEntryPoint();
    }

    /**
     * Occurs after the install command has been executed with a lock file present.
     *
     * @param Event $event
     */
    public static function postInstall(Event $event)
    {
        static::copyDefaultConfiguration();
        static::copyInstallEnvironment();
        static::copyDevEnvironment();
        static::copyClearCacheEntryPoint();
    }

    public static function warmCache(Event $event)
    {
        // make cache toasty
    }

    public static function copyDefaultConfiguration()
    {
        $fs = new Filesystem();
        $configFile = 'conf/config.yml';
        $configFileSrc = 'conf/config.default.yml';

        if (!$fs->exists($configFile) &&
            $fs->exists($configFileSrc)) {
            $fs->copy($configFileSrc, $configFile);
            echo 'Copying conf/config.yml default configuration.' . PHP_EOL;
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
            echo 'Copying install.php entry point.' . PHP_EOL;
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
            echo 'Copying dev.php entry point.' . PHP_EOL;
        }
    }

    public static function copyClearCacheEntryPoint()
    {
        $fs = new Filesystem();
        $clearCacheFile = 'clear_cache.php';
        $clearCacheFileSrc = 'samples/clear_cache.php.sample';

        if (!$fs->exists($clearCacheFile) &&
            $fs->exists($clearCacheFileSrc)) {
            $fs->copy($clearCacheFileSrc, $clearCacheFile);
            echo 'Copying clear_cache.php entry point.' . PHP_EOL;
        }
    }
}
