<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Pimple\Container;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Preview\PreviewResolverInterface;

final class RoadizRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of EntityRepository instances.
     *
     * @var ObjectRepository[]
     */
    private array $repositoryList = [];
    private Container $container;
    private PreviewResolverInterface $previewResolver;

    /**
     * @param Container $container
     * @param PreviewResolverInterface $previewResolver
     */
    public function __construct(Container $container, PreviewResolverInterface $previewResolver)
    {
        $this->container = $container;
        $this->previewResolver = $previewResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repositoryHash = $entityManager->getClassMetadata($entityName)->getName() . spl_object_hash($entityManager);

        if (isset($this->repositoryList[$repositoryHash])) {
            return $this->repositoryList[$repositoryHash];
        }

        return $this->repositoryList[$repositoryHash] = $this->createRepository($entityManager, $entityName);
    }

    /**
     * Create a new repository instance for an entity class.
     *
     * @param EntityManagerInterface $entityManager The EntityManager instance.
     * @param class-string $entityName The name of the entity.
     *
     * @return ObjectRepository
     */
    private function createRepository(EntityManagerInterface $entityManager, string $entityName)
    {
        $metadata = $entityManager->getClassMetadata($entityName);
        $repositoryClassName = $metadata->customRepositoryClassName
            ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        if (is_subclass_of($repositoryClassName, EntityRepository::class) ||
            $repositoryClassName == EntityRepository::class) {
            return new $repositoryClassName($entityManager, $metadata, $this->container, $this->previewResolver);
        }

        return new $repositoryClassName($entityManager, $metadata);
    }
}
