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
 * @file SLIRConfig.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Utils;

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
        static::$pathToCacheDir =                      ROADIZ_ROOT.'/cache'; // Place cache dir outside of VENDOR to make updates easier.
        static::$pathToErrorLog =                      ROADIZ_ROOT.'/files/slir-error-log';
        static::$documentRoot =                        ROADIZ_ROOT.'/files'; // RZ_CMS The document root is used directly in the rz-core document class.
        static::$urlToSLIR =                           '/assets';
        static::$maxMemoryToAllocate =                 64;
        // This must be the last line of this function
        parent::init();
    }
}

SLIRConfig::init();
