<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file StatusAwareRepository.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\QueryBuilder;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class StatusAwareRepository extends EntityRepository
{
    /**
     * @var bool
     */
    private $displayNotPublishedNodes;

    /**
     * @var bool
     */
    private $displayAllNodesStatuses;

    /**
     * @inheritDoc
     */
    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, Container $container, $isPreview = false)
    {
        parent::__construct($em, $class, $container, $isPreview);

        $this->displayNotPublishedNodes = false;
        $this->displayAllNodesStatuses = false;
    }


    /**
     * @return bool
     */
    public function isDisplayingNotPublishedNodes()
    {
        return $this->displayNotPublishedNodes;
    }

    /**
     * @param bool $displayNotPublishedNodes
     * @return StatusAwareRepository
     */
    public function setDisplayingNotPublishedNodes($displayNotPublishedNodes)
    {
        $this->displayNotPublishedNodes = $displayNotPublishedNodes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisplayingAllNodesStatuses()
    {
        return $this->displayAllNodesStatuses;
    }

    /**
     * Switch repository to disable any security on Node status. To use ONLY in order to
     * view deleted and archived nodes.
     *
     * @param bool $displayAllNodesStatuses
     * @return StatusAwareRepository
     */
    public function setDisplayingAllNodesStatuses($displayAllNodesStatuses)
    {
        $this->displayAllNodesStatuses = $displayAllNodesStatuses;
        return $this;
    }

    /**
     * @return bool
     */
    protected function isBackendUserWithPreview()
    {
        /** @var AuthorizationCheckerInterface|null $checker */
        $checker = $this->get('securityAuthorizationChecker');
        try {
            return $this->isPreview === true && null !== $checker && $checker->isGranted(Role::ROLE_BACKEND_USER);
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $prefix
     * @return QueryBuilder
     */
    protected function alterQueryBuilderWithAuthorizationChecker(
        QueryBuilder $qb,
        $prefix = EntityRepository::NODE_ALIAS
    ) {
        if (true === $this->isDisplayingAllNodesStatuses()) {
            // do not filter on status
            return $qb;
        }
        /*
         * Check if user can see not-published node based on its Token
         * and context.
         */
        if (true === $this->isDisplayingNotPublishedNodes() || $this->isBackendUserWithPreview()) {
            $qb->andWhere($qb->expr()->lte($prefix . '.status', Node::PUBLISHED));
        } else {
            $qb->andWhere($qb->expr()->eq($prefix . '.status', Node::PUBLISHED));
        }

        return $qb;
    }
}
