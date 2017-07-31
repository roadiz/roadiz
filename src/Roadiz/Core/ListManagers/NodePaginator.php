<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodePaginator.php
 * @author Ambroise Maupate
 */
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
            return $this->em->getRepository($this->entityName)
                ->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes())
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
                $this->totalCount = $this->em->getRepository($this->entityName)
                    ->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes())
                    ->countSearchBy($this->searchPattern, $this->criteria);
            } else {
                $this->totalCount = $this->em->getRepository($this->entityName)
                    ->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes())
                    ->countBy(
                        $this->criteria,
                        $this->translation
                    );
            }
        }

        return $this->totalCount;
    }
}
