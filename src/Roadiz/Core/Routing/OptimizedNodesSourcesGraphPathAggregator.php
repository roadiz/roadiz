<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;

final class OptimizedNodesSourcesGraphPathAggregator implements NodesSourcesPathAggregator
{
    private EntityManagerInterface $entityManager;
    private ArrayCache $cache;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->cache = new ArrayCache();
    }

    /**
     * @param NodesSources $nodesSources
     * @param array        $parameters
     *
     * @return string
     */
    public function aggregatePath(NodesSources $nodesSources, array $parameters = []): string
    {
        if (isset($parameters[NodeRouter::NO_CACHE_PARAMETER]) &&
            $parameters[NodeRouter::NO_CACHE_PARAMETER] === true) {
            $urlTokens = array_reverse($this->getIdentifiers($nodesSources));
            return implode('/', $urlTokens);
        }

        if (!$this->cache->contains($nodesSources->getId())) {
            $urlTokens = array_reverse($this->getIdentifiers($nodesSources));
            $this->cache->save($nodesSources->getId(), implode('/', $urlTokens));
        }
        return $this->cache->fetch($nodesSources->getId());
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

    /**
     * Get every nodeSource parents identifier from current to
     * farest ancestor.
     *
     * @param NodesSources $source
     *
     * @return array
     */
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
                    ->getQuery()
                    ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                    ->setCacheable(true)
                    ->getArrayResult()
                ;
                usort($parents, function ($a, $b) use ($parentIds) {
                    return array_search($a['id'], $parentIds) -
                        array_search($b['id'], $parentIds);
                });
            }
        }

        $urlTokens[] = $source->getIdentifier();

        foreach ($parents as $parent) {
            $urlTokens[] = $parent['alias'] ?? $parent['nodeName'];
        }

        return $urlTokens;
    }
}
