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
 * @file index.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Kernel;

if (version_compare(phpversion(), '5.4.3', '<')) {
    echo 'Your PHP version is ' . phpversion() . "." . PHP_EOL;
    echo 'You need a least PHP version 5.4.3';
    exit(1);
}

require 'bootstrap.php';

if (php_sapi_name() == 'cli') {
    echo 'Use "bin/roadiz" as an executable instead of calling index.php' . PHP_EOL;
} else {
    try {
        $request = Kernel::getInstance()->getRequest();

        Kernel::getInstance()->boot();
        /*
         * Bypass Roadiz kernel to directly serve SLIR assets
         */
        if (0 === strpos($request->getPathInfo(), '/assets') &&
            preg_match('#^/assets/(?P<queryString>[a-zA-Z:0-9\\-]+)/(?P<filename>[a-zA-Z0-9\\-_\\./]+)$#s', $request->getPathInfo(), $matches)
        ) {
            $ctrl = new \RZ\Roadiz\CMS\Controllers\AssetsController();
            $ctrl->slirAction($matches['queryString'], $matches['filename']);
        } else {
            /*
             * Start Roadiz App handling
             */
            Kernel::getInstance()->runApp();
        }
    } catch (NoConfigurationFoundException $e) {
        $response = Kernel::getInstance()->getEmergencyResponse($e);
        $response->send();
    }
}
