<?php
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
     * Checks if preview mode is enabled.
     *
     * @return bool true if preview mode is enabled, false otherwise
     * @deprecated Use request-time preview
     */
    public function isPreview();

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir();

    /**
     * Gets the application vendor dir (path of the project's composer folder).
     *
     * @return string The vendor dir
     */
    public function getVendorDir();

    /**
     * Gets the application root dir (path of the project's AppKernel class).
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
