<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Contracts\NodeType\NodeTypeResolverInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Repositories\NodeTypeRepository;

class NodeTypes extends LazyParameterBag implements NodeTypeResolverInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var NodeTypeRepository
     */
    private $repository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * @return NodeTypeRepository
     */
    public function getRepository(): NodeTypeRepository
    {
        if (null === $this->repository) {
            $this->repository = $this->entityManager->getRepository(NodeType::class);
        }
        return $this->repository;
    }

    protected function populateParameters()
    {
        try {
            $nodeTypes = $this->getRepository()->findAll();
            $this->parameters = [];
            /** @var NodeType $nodeType */
            foreach ($nodeTypes as $nodeType) {
                $this->parameters[$nodeType->getName()] = $nodeType;
                $this->parameters[$nodeType->getSourceEntityFullQualifiedClassName()] = $nodeType;
            }
        } catch (DBALException $e) {
            $this->parameters = [];
        }
        $this->ready = true;
    }
}
