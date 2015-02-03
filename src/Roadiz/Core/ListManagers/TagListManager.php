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
 * @file TagListManager.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\ListManagers;

use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityManager;

/**
 * Perform basic filtering and search over entity listings.
 */
class TagListManager extends EntityListManager
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Doctrine\ORM\EntityManager               $_em
     * @param array                                    $preFilters
     * @param array                                    $preOrdering
     */
    public function __construct(Request $request, EntityManager $_em, $preFilters = [], $preOrdering = [])
    {
        parent::__construct($request, $_em, 'RZ\Roadiz\Core\Entities\Tag', $preFilters, $preOrdering);
    }

    /**
     * Return filtered entities.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEntities()
    {
        try {
            if ($this->searchPattern != '') {
                return $this->_em
                    ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                    ->searchBy($this->searchPattern, $this->filteringArray, $this->orderingArray);
            } else {
                return $this->paginator->findByAtPage($this->filteringArray, $this->currentPage);
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
