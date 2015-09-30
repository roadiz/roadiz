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
 * @file NodesSourcesPaginator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\ListManagers;

use RZ\Roadiz\Core\ListManagers\Paginator;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * A paginator class to filter node-sources entities with limit and search.
 *
 * This class add authorizationChecker filters
 */
class NodesSourcesPaginator extends Paginator
{
    protected $authorizationChecker = null;
    protected $preview = false;

    /**
     * @return AuthorizationChecker
     */
    public function getAuthorizationChecker()
    {
        return $this->authorizationChecker;
    }

    /**
     * @param AuthorizationChecker $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker = null)
    {
        $this->authorizationChecker = $authorizationChecker;

        return $this;
    }

    /**
     * Return page count according to criteria.
     *
     * **Warning** : EntityRepository must implements *countBy* method
     *
     * @return integer
     */
    public function getPageCount()
    {
        if (null !== $this->searchPattern) {
            $total = $this->em->getRepository($this->entityName)
                ->countSearchBy($this->searchPattern, $this->criteria);
        } else {
            $total = $this->em->getRepository($this->entityName)
                ->countBy(
                    $this->criteria,
                    $this->authorizationChecker,
                    $this->preview
                );
        }

        return ceil($total / $this->getItemsPerPage());
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByAtPage(array $order = [], $page = 1)
    {
        if (null !== $this->searchPattern) {
            return $this->searchByAtPage($order, $page);
        } else {
            return $this->em->getRepository($this->entityName)
                ->findBy(
                    $this->criteria,
                    $order,
                    $this->getItemsPerPage(),
                    $this->getItemsPerPage() * ($page - 1),
                    $this->authorizationChecker,
                    $this->preview
                );
        }
    }

    /**
     * Gets the value of preview.
     *
     * @return mixed
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * Sets the value of preview.
     *
     * @param boolean $preview the preview
     *
     * @return self
     */
    protected function setPreview($preview)
    {
        $this->preview = (boolean) $preview;

        return $this;
    }
}
