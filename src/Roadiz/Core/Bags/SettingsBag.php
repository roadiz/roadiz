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
 * @file SettingsBag.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Bags;

use RZ\Roadiz\Core\Kernel;

/**
 * Settings bag used to get quickly a setting value.
 */
class SettingsBag
{
    /**
     * Cached settings values.
     *
     * @var array
     */
    protected static $settings = [];

    /**
     * Get a setting value from its name.
     *
     * @param string $settingName
     *
     * @return string|boolean
     */
    public static function get($settingName)
    {
        if (!isset(static::$settings[$settingName]) &&
            Kernel::getService('em') !== null) {
            try {
                static::$settings[$settingName] =
                            Kernel::getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                            ->getValue($settingName);
            } catch (\Exception $e) {
                return false;
            }
        }

        return isset(static::$settings[$settingName]) ? static::$settings[$settingName] : false;
    }

    /**
     * Get a document from its setting name.
     *
     * @param string $settingName
     *
     * @return \RZ\Roadiz\Core\Entities\Document|null
     */
    public static function getDocument($settingName)
    {
        if (!isset(static::$settings[$settingName]) &&
            Kernel::getService('em') !== null) {
            try {
                $id = Kernel::getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                            ->getValue($settingName);

                static::$settings[$settingName] = Kernel::getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Document', (int) $id);

            } catch (\Exception $e) {
                return false;
            }
        }

        return isset(static::$settings[$settingName]) ? static::$settings[$settingName] : false;
    }
}
