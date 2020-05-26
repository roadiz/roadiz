<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use RZ\Roadiz\Core\Entities\Translation;

/**
 * A paginator class to filter node entities with limit and search.
 *
 * This class add some translation and security filters
 */
class NodePaginator extends Paginator
{
    protected $translation = null;

    /**
     * @return \RZ\Roadiz\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param  \RZ\Roadiz\Core\Entities\Translation $newtranslation
     * @return $this
     */
    public function setTranslation(Translation $newtranslation = null)
    {
        $this->translation = $newtranslation;
        return $this;
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return array
     */
    public function findByAtPage(array $order = [], $page = 1)
    {
        if (null !== $this->searchPattern) {
            return $this->searchByAtPage($order, $page);
        } else {
            return $this->getRepository()
                ->findBy(
                    $this->criteria,
                    $order,
                    $this->getItemsPerPage(),
                    $this->getItemsPerPage() * ($page - 1),
                    $this->translation
                );
        }
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        if (null === $this->totalCount) {
            if (null !== $this->searchPattern) {
                $this->totalCount = $this->getRepository()
                    ->countSearchBy($this->searchPattern, $this->criteria);
            } else {
                $this->totalCount = $this->getRepository()
                    ->countBy(
                        $this->criteria,
                        $this->translation
                    );
            }
        }

        return $this->totalCount;
    }
}
