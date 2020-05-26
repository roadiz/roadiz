<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Repositories\NodeTypeRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

class NodeTypes extends ParameterBag
{
    /**
     * @var bool
     */
    private $ready;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var NodeTypeRepository
     */
    private $repository;

    /**
     * SettingsBag constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->ready = false;
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
            }
        } catch (DBALException $e) {
            $this->parameters = [];
        }
        $this->ready = true;
    }

    /**
     * @param string $key
     * @param null $default
     * @return bool|mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::get($key, null);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::all();
    }


    public function reset(): void
    {
        $this->parameters = [];
        $this->ready = false;
    }
}
