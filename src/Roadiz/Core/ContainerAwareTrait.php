<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core;

use Pimple\Container;

trait ContainerAwareTrait
{
    protected ?Container $container = null;

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($serviceName)
    {
        return $this->container->offsetGet($serviceName);
    }

    /**
     * {@inheritdoc}
     */
    public function has($serviceName)
    {
        return $this->container->offsetExists($serviceName);
    }
}
