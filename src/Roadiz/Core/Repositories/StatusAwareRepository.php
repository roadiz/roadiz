<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\QueryBuilder;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @template T
 * @extends EntityRepository<T>
 */
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
    public function __construct(
        EntityManager $em,
        Mapping\ClassMetadata $class,
        Container $container,
        PreviewResolverInterface $previewResolver
    ) {
        parent::__construct($em, $class, $container, $previewResolver);

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
     *
     * @return StatusAwareRepository
     */
    public function setDisplayingAllNodesStatuses($displayAllNodesStatuses)
    {
        $this->displayAllNodesStatuses = $displayAllNodesStatuses;
        return $this;
    }

    /**
     * @return bool
     * @deprecated Do not depend on granted ROLE, preview logic can vary
     */
    protected function isBackendUserWithPreview()
    {
        /** @var AuthorizationCheckerInterface|null $checker */
        $checker = $this->get('securityAuthorizationChecker');
        try {
            return $this->previewResolver->isPreview() &&
                null !== $checker &&
                $checker->isGranted(Role::ROLE_BACKEND_USER);
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
        if (true === $this->isDisplayingNotPublishedNodes() || $this->previewResolver->isPreview()) {
            $qb->andWhere($qb->expr()->lte($prefix . '.status', Node::PUBLISHED));
        } else {
            $qb->andWhere($qb->expr()->eq($prefix . '.status', Node::PUBLISHED));
        }

        return $qb;
    }
}
