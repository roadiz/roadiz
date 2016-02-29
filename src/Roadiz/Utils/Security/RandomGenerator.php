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
 * @file RandomGenerator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Security;

use Psr\Log\LoggerInterface;

class RandomGenerator
{
    protected $logger;
    protected $useOpenSsl;

    /**
     * @param LoggerInterfacee|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        // determine whether to use OpenSSL
        if (defined('PHP_WINDOWS_VERSION_BUILD') && version_compare(PHP_VERSION, '5.3.4', '<')) {
            $this->useOpenSsl = false;
        } elseif (!function_exists('openssl_random_pseudo_bytes')) {
            if (null !== $this->logger) {
                $this->logger->notice('It is recommended that you enable the "openssl" extension for random number generation.');
            }
            $this->useOpenSsl = false;
        } else {
            $this->useOpenSsl = true;
        }
    }
}
