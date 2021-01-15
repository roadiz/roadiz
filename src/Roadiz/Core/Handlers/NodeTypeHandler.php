<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\EntityGenerator\EntityGeneratorFactory;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Handle operations with node-type entities.
 */
class NodeTypeHandler extends AbstractHandler
{
    /**
     * @var NodeType
     */
    private $nodeType;
    /**
     * @var Kernel
     */
    private $kernel;
    /**
     * @var EntityGeneratorFactory
     */
    private $entityGeneratorFactory;
    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * @return NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param NodeType $nodeType
     * @return $this
     */
    public function setNodeType(NodeType $nodeType)
    {
        $this->nodeType = $nodeType;
        return $this;
    }

    /**
     * Create a new node-type handler with node-type to handle.
     *
     * @param ObjectManager $objectManager
     * @param Kernel $kernel
     * @param EntityGeneratorFactory $entityGeneratorFactory
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(
        ObjectManager $objectManager,
        Kernel $kernel,
        EntityGeneratorFactory $entityGeneratorFactory,
        HandlerFactory $handlerFactory
    ) {
        parent::__construct($objectManager);
        $this->kernel = $kernel;
        $this->entityGeneratorFactory = $entityGeneratorFactory;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @return string
     */
    public function getGeneratedEntitiesFolder()
    {
        return $this->kernel->getRootDir() . '/gen-src/' . NodeType::getGeneratedEntitiesNamespace();
    }

    /**
     * Remove node type entity class file from server.
     *
     */
    public function removeSourceEntityClass()
    {
        $file = $this->getSourceClassPath();
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($file) && is_file($file)) {
            $fileSystem->remove($file);
            return true;
        }

        return false;
    }

    /**
     * Generate Doctrine entity class for current node-type.
     *
     * @return bool
     */
    public function generateSourceEntityClass()
    {
        $folder = $this->getGeneratedEntitiesFolder();
        $file = $this->getSourceClassPath();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($folder)) {
            $fileSystem->mkdir($folder, 0775);
        }

        if (!$fileSystem->exists($file)) {
            $classGenerator = $this->entityGeneratorFactory->create($this->nodeType);
            $content = $classGenerator->getClassContent();

            if (false === @file_put_contents($file, $content)) {
                throw new IOException("Impossible to write entity class file (".$file.").", 1);
            }
            /*
             * Force Zend OPcache to reset file
             */
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file, true);
            }
            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }

            return true;
        }
        return false;
    }

    public function getSourceClassPath(): string
    {
        $folder = $this->getGeneratedEntitiesFolder();
        return $folder.'/'.$this->nodeType->getSourceEntityClassName().'.php';
    }

    /**
     * Clear doctrine metadata cache and
     * regenerate entity class file.
     *
     * @return $this
     */
    public function updateSchema()
    {
        $this->clearCaches(false);
        $this->regenerateEntityClass();
        // Clear cache only after generating NSEntity class.
        $this->clearCaches();

        return $this;
    }

    /**
     * Delete and recreate entity class file.
     */
    public function regenerateEntityClass()
    {
        $this->removeSourceEntityClass();
        $this->generateSourceEntityClass();

        return $this;
    }

    /**
     * Delete node-type class from database.
     *
     * @return $this
     */
    public function deleteSchema()
    {
        $this->removeSourceEntityClass();
        $this->clearCaches();

        return $this;
    }

    /**
     * @param bool $recreateProxies
     */
    protected function clearCaches(bool $recreateProxies = true)
    {
        $clearers = [
            new OPCacheClearer(),
        ];

        if ($this->objectManager instanceof EntityManagerInterface) {
            $clearers[] = new DoctrineCacheClearer($this->objectManager, $this->kernel, $recreateProxies);
        }

        foreach ($clearers as $clearer) {
            $clearer->clear();
        }
    }

    /**
     * Delete node-type inherited nodes and its database schema
     * before removing it from node-types table.
     *
     * @return $this
     */
    public function deleteWithAssociations()
    {
        /*
         * Delete every nodes
         */
        $nodes = $this->objectManager
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
            ->findBy([
                'nodeType' => $this->getNodeType(),
            ]);

        /** @var Node $node */
        foreach ($nodes as $node) {
            /** @var NodeHandler $nodeHandler */
            $nodeHandler = $this->handlerFactory->getHandler($node);
            $nodeHandler->removeWithChildrenAndAssociations();
        }

        /*
         * Remove node type
         */
        $this->objectManager->remove($this->getNodeType());
        $this->objectManager->flush();

        /*
         * Remove class and database table
         */
        $this->deleteSchema();

        return $this;
    }

    /**
     * Reset current node-type fields positions.
     *
     * @param bool $setPosition
     * @return int Return the next position after the **last** field
     */
    public function cleanPositions($setPosition = false)
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['position' => 'ASC']);
        $fields = $this->nodeType->getFields()->matching($criteria);
        $i = 1;
        /** @var NodeTypeField $field */
        foreach ($fields as $field) {
            $field->setPosition($i);
            $i++;
        }

        return $i;
    }
}
