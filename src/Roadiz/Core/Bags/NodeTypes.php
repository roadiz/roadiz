<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Bag\LazyParameterBag;
use RZ\Roadiz\Contracts\NodeType\NodeTypeResolverInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Repositories\NodeTypeRepository;

class NodeTypes extends LazyParameterBag implements NodeTypeResolverInterface
{
    private ?NodeTypeRepository $repository = null;
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return NodeTypeRepository
     */
    public function getRepository(): NodeTypeRepository
    {
        if (null === $this->repository) {
            $this->repository = $this->managerRegistry->getRepository(NodeType::class);
        }
        return $this->repository;
    }

    protected function populateParameters(): void
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
