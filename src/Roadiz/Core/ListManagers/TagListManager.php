<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Perform basic filtering and search over entity listings.
 */
class TagListManager extends EntityListManager
{
    /**
     * @param Request|null  $request
     * @param EntityManagerInterface $entityManager
     * @param array         $preFilters
     * @param array         $preOrdering
     */
    public function __construct(
        ?Request $request,
        EntityManagerInterface $entityManager,
        $preFilters = [],
        $preOrdering = []
    ) {
        parent::__construct($request, $entityManager, Tag::class, $preFilters, $preOrdering);
    }

    /**
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator|null
     */
    public function getEntities()
    {
        try {
            if ($this->searchPattern != '') {
                return $this->entityManager
                    ->getRepository(TagTranslation::class)
                    ->searchBy($this->searchPattern, $this->filteringArray, $this->orderingArray);
            } else {
                return $this->paginator->findByAtPage($this->filteringArray, $this->currentPage);
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
