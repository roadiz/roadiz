<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */

declare(strict_types=1);

namespace RZ\Roadiz\Core;

use Pimple\Container;
use Symfony\Component\HttpKernel\HttpKernelInterface;

interface KernelInterface extends HttpKernelInterface, \Serializable
{
    /**
     * Boots the current kernel.
     */
    public function boot();

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     */
    public function shutdown();

    /**
     * Gets the name of the kernel.
     *
     * @return string The kernel name
     */
    public function getName();

    /**
     * Gets the environment.
     *
     * @return string The current environment
     */
    public function getEnvironment();

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool true if debug mode is enabled, false otherwise
     */
    public function isDebug();

    /**
     * Gets the application root dir (path of the project's Kernel class).
     *
     * @return string The Kernel root dir
     */
    public function getRootDir();

    /**
     * Gets the current container.
     *
     * @return Container|null A Container instance or null when the Kernel is shutdown
     */
    public function getContainer();

    /**
     * Gets the request start time (not available if debug is disabled).
     *
     * @return float The request start timestamp
     */
    public function getStartTime();

    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     */
    public function getCacheDir();

    /**
     * Gets the log directory.
     *
     * @return string The log directory
     */
    public function getLogDir();

    /**
     * Gets the charset of the application.
     *
     * @return string The charset
     */
    public function getCharset();
}
