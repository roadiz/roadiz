<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core;

use Pimple\Container;

/**
 * @package RZ\Roadiz\Core
 */
interface ContainerAwareInterface
{
    /**
     * @return Container
     */
    public function getContainer();

    /**
     * @param Container $container
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container);

    /**
     * Return a service from container.
     *
     * @param string $serviceName
     * @return mixed
     */
    public function get($serviceName);

    /**
     * Returns true if the service is defined.
     *
     * @param string $serviceName
     * @return bool true if the service is defined, false otherwise
     */
    public function has($serviceName);
}
