<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core;

/**
 * DevKernel is meant for Vagrant and Docker development env
 * where using file sharing on Roadiz folder.
 *
 * @see http://www.whitewashing.de/2013/08/19/speedup_symfony2_on_vagrant_boxes.html
 * @package RZ\Roadiz\Core
 */
class DevKernel extends Kernel
{
    private string $appName;

    /**
     * @param string $environment
     * @param boolean $debug
     * @param bool $preview
     * @param string $appName
     */
    public function __construct(string $environment, bool $debug, bool $preview = false, string $appName = "roadiz_dev")
    {
        parent::__construct($environment, $debug, $preview);

        $this->appName = $appName;
    }

    /**
     * It’s important to set cache dir outside of any shared folder. RAM disk is a good idea.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return '/dev/shm/' . $this->appName . '/cache/' .  $this->environment;
    }

    /**
     * It’s important to set logs dir outside of any shared folder. RAM disk is a good idea.
     *
     * @return string
     */
    public function getLogDir()
    {
        return '/dev/shm/' . $this->appName . '/logs';
    }
}
