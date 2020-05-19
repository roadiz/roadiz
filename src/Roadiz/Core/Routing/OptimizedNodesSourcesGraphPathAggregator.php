<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;

final class OptimizedNodesSourcesGraphPathAggregator implements NodesSourcesPathAggregator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * NodesSourcesPathResolver constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param NodesSources $nodesSources
     *
     * @return string
     */
    public function aggregatePath(NodesSources $nodesSources): string
    {
        $urlTokens = array_reverse($this->getIdentifiers($nodesSources));
        return implode('/', $urlTokens);
    }

    /**
     * @param Node $parent
     *
     * @return array
     */
    protected function getParentsIds(Node $parent): array
    {
        $parentIds = [];
        while ($parent !== null && !$parent->isHome()) {
            $parentIds[] = $parent->getId();
            $parent = $parent->getParent();
        }

        return $parentIds;
    }

    protected function getIdentifiers(NodesSources $source): array
    {
        $urlTokens = [];
        $parents = [];
        /** @var Node|null $parentNode */
        $parentNode = $source->getNode()->getParent();

        if (null !== $parentNode) {
            $parentIds = $this->getParentsIds($parentNode);
            if (count($parentIds) > 0) {
                /**
                 * @var QueryBuilder $qb
                 *
                 * Do a partial query to optimize SQL time
                 */
                $qb = $this->entityManager
                    ->getRepository(NodesSources::class)
                    ->createQueryBuilder('ns');
                $parents = $qb->select('n.id as id, n.nodeName as nodeName, ua.alias as alias')
                    ->innerJoin('ns.node', 'n')
                    ->leftJoin('ns.urlAliases', 'ua')
                    ->andWhere($qb->expr()->in('n.id', ':parentIds'))
                    ->andWhere($qb->expr()->eq('n.visible', ':visible'))
                    ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
                    ->setParameters([
                        'parentIds' => $parentIds,
                        'visible' => true,
                        'translation' => $source->getTranslation()
                    ])
                    ->setCacheable(true)
                    ->getQuery()
                    ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                    ->getArrayResult()
                ;
                usort($parents, function ($a, $b) use ($parentIds) {
                    return array_search($a['id'], $parentIds) -
                        array_search($b['id'], $parentIds);
                });
            }
        }

        if ($source->getNode()->isVisible()) {
            $urlTokens[] = $source->getIdentifier();
        }
        foreach ($parents as $parent) {
            $urlTokens[] = $parent['alias'] ?? $parent['nodeName'];
        }

        return $urlTokens;
    }
}
