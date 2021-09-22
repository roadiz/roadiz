<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

/**
 * Wrapper around the only Doctrine manager for Roadiz CMS.
 *
 * @package RZ\Roadiz\Utils\Doctrine
 */
final class RoadizManagerRegistry implements ManagerRegistry
{
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConnectionName()
    {
        return get_class($this->container->get('em')->getConnection());
    }

    /**
     * @inheritDoc
     */
    public function getConnection($name = null)
    {
        return $this->container->get('em')->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function getConnections()
    {
        return [
            $this->container->get('em'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getConnectionNames()
    {
        return [
            $this->getDefaultConnectionName(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultManagerName()
    {
        return get_class($this->container->get('em'));
    }

    /**
     * @inheritDoc
     */
    public function getManager($name = null)
    {
        return $this->container->get('em');
    }

    /**
     * @inheritDoc
     */
    public function getManagers()
    {
        return [
            $this->getManager(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function resetManager($name = null)
    {
        throw new \LogicException('Roadiz does not support ManagerRegistry reset');
    }

    /**
     * @inheritDoc
     */
    public function getAliasNamespace($alias)
    {
        throw new \LogicException('Roadiz does not support ManagerRegistry namespace aliases');
    }

    /**
     * @inheritDoc
     */
    public function getManagerNames()
    {
        return [
            $this->getDefaultManagerName(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->getManagerForClass($persistentObject)->getRepository($persistentObject);
    }

    /**
     * @inheritDoc
     */
    public function getManagerForClass($class)
    {
        return $this->getManager();
    }
}
