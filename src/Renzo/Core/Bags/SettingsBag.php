<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SettingsBag.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Bags;

use RZ\Renzo\Core\Kernel;

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
    private static $settings = array();

    /**
     * Get a setting value from its name.
     *
     * @param string $settingName
     *
     * @return string or false
     */
    public static function get($settingName)
    {
        if (!isset(static::$settings[$settingName]) &&
            Kernel::getService('em') !== null) {

            try {
                static::$settings[$settingName] =
                            Kernel::getService('em')
                            ->getRepository('RZ\Renzo\Core\Entities\Setting')
                            ->getValue($settingName);
            } catch (\Exception $e) {
                return false;
            }
        }

        return isset(static::$settings[$settingName]) ? static::$settings[$settingName] : false;
    }
}
