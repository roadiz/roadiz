<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

class QueryBuilderListManager extends AbstractEntityListManager
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;
    /**
     * @var Paginator|null
     */
    protected $paginator;
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @param Request|null $request
     * @param QueryBuilder $queryBuilder
     * @param string $identifier
     * @param bool $debug
     */
    public function __construct(
        ?Request $request,
        QueryBuilder $queryBuilder,
        string $identifier = 'obj',
        bool $debug = false
    ) {
        parent::__construct($request);
        $this->queryBuilder = $queryBuilder;
        $this->identifier = $identifier;
        $this->request = $request;
        $this->debug = $debug;
    }

    /**
     * @param string $search
     */
    protected function handleSearchParam(string $search): void
    {
        // Implement your custom logic
    }

    /**
     * @inheritDoc
     */
    public function handle($disabled = false)
    {
        if (false === $disabled && null !== $this->request) {
            if ($this->request->query->get('field') &&
                $this->request->query->get('ordering')) {
                $this->queryBuilder->addOrderBy(
                    sprintf('%s.%s', $this->identifier, $this->request->query->get('field')),
                    $this->request->query->get('ordering')
                );
                $this->queryArray['field'] = $this->request->query->get('field');
                $this->queryArray['ordering'] = $this->request->query->get('ordering');
            }

            if ($this->request->query->get('search') != "") {
                $this->handleSearchParam($this->request->query->get('search'));
                $this->queryArray['search'] = $this->request->query->get('search');
            }

            if ($this->request->query->has('item_per_page') &&
                $this->request->query->get('item_per_page') > 0) {
                $this->setItemPerPage($this->request->query->get('item_per_page'));
            }

            if ($this->request->query->has('page') &&
                $this->request->query->get('page') > 1) {
                $this->setPage($this->request->query->get('page'));
            } else {
                $this->setPage(1);
            }
        } else {
            /*
             * Disable pagination and paginator
             */
            $this->disablePagination();
        }

        $this->paginator = new Paginator($this->queryBuilder);
    }

    /**
     * @inheritDoc
     */
    public function setPage($page)
    {
        parent::setPage($page);
        $this->queryBuilder->setFirstResult($this->getItemPerPage() * ($page - 1));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setItemPerPage($itemPerPage)
    {
        parent::setItemPerPage($itemPerPage);
        $this->queryBuilder->setMaxResults((int) $itemPerPage);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getItemCount()
    {
        if (null !== $this->paginator) {
            return $this->paginator->count();
        }
        throw new \InvalidArgumentException('Call EntityListManagerInterface::handle before counting entities.');
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return (int) ceil($this->getItemCount() / $this->getItemPerPage());
    }

    /**
     * @inheritDoc
     */
    public function getEntities()
    {
        if (null !== $this->paginator) {
            return $this->paginator;
        }
        throw new \InvalidArgumentException('Call EntityListManagerInterface::handle before getting entities.');
    }

    /**
     * @return array
     */
    public function getAssignation()
    {
        if ($this->debug) {
            return array_merge(parent::getAssignation(), [
                'dql_query' => $this->queryBuilder->getDQL()
            ]);
        }
        return parent::getAssignation();
    }
}
