<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Wrapper around the only Doctrine manager for Roadiz CMS.
 *
 * @package RZ\Roadiz\Utils\Doctrine
 */
final class RoadizManagerRegistry implements ManagerRegistry
{
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConnectionName()
    {
        return get_class($this->entityManager->getConnection());
    }

    /**
     * @inheritDoc
     */
    public function getConnection($name = null)
    {
        return $this->entityManager->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function getConnections()
    {
        return [
            $this->getConnection(),
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
        return get_class($this->entityManager);
    }

    /**
     * @inheritDoc
     */
    public function getManager($name = null)
    {
        return $this->entityManager;
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
