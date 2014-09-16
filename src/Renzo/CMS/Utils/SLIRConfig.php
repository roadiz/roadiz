<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SLIRConfig.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Utils;

/**
 * SLIR Config Class.
 */
class SLIRConfig extends \SLIR\SLIRConfigDefaults
{
    /**
     * Override default SLIR configuration values.
     */
    public static function init()
    {
        // This must be the last line of this function
        static::$garbageCollectDivisor =               400;
        static::$garbageCollectFileCacheMaxLifetime =  345600;
        static::$browserCacheTTL  =                    604800; // 7*24*60*60
        static::$pathToCacheDir =                      RENZO_ROOT.'/cache'; // Place cache dir outside of VENDOR to make updates easier.
        static::$pathToErrorLog =                      RENZO_ROOT.'/files/slir-error-log';
        static::$documentRoot =                        RENZO_ROOT.'/files'; // RZ_CMS The document root is used directly in the rz-core document class.
        static::$urlToSLIR =                           '/assets';
        static::$maxMemoryToAllocate =                 64;
        // This must be the last line of this function
        parent::init();
    }
}

SLIRConfig::init();
