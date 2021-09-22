<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Core\Routing\NodeRouter;
use RZ\Roadiz\Utils\Node\Exception\SameNodeUrlException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class NodeMover
{
    protected ManagerRegistry $managerRegistry;
    protected UrlGeneratorInterface $urlGenerator;
    protected HandlerFactoryInterface $handlerFactory;
    protected EventDispatcherInterface $dispatcher;
    protected CacheProvider $cacheProvider;
    protected LoggerInterface $logger;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param UrlGeneratorInterface $urlGenerator
     * @param HandlerFactoryInterface $handlerFactory
     * @param EventDispatcherInterface $dispatcher
     * @param CacheProvider $cacheProvider
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        UrlGeneratorInterface $urlGenerator,
        HandlerFactoryInterface $handlerFactory,
        EventDispatcherInterface $dispatcher,
        CacheProvider $cacheProvider,
        ?LoggerInterface $logger = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger ?? new NullLogger();
        $this->dispatcher = $dispatcher;
        $this->cacheProvider = $cacheProvider;
        $this->handlerFactory = $handlerFactory;
        $this->managerRegistry = $managerRegistry;
    }

    private function getManager(): ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(Redirection::class);
        if (null === $manager) {
            throw new \RuntimeException('No manager was found during transtyping.');
        }
        return $manager;
    }

    /**
     * Warning: this method DOES NOT flush entity manager.
     *
     * @param Node      $node
     * @param Node|null $parentNode
     * @param float     $position
     * @param bool      $force
     * @param bool      $cleanPosition
     *
     * @return Node
     */
    public function move(
        Node $node,
        ?Node $parentNode,
        float $position,
        bool $force = false,
        bool $cleanPosition = true
    ): Node {
        if ($node->isLocked() && $force === false) {
            throw new BadRequestHttpException('Locked node cannot be moved.');
        }

        if ($node->getParent() !== $parentNode) {
            $node->setParent($parentNode);
        }

        $node->setPosition($position);

        if ($cleanPosition) {
            $this->getManager()->flush();
            /** @var NodeHandler $nodeHandler */
            $nodeHandler = $this->handlerFactory->getHandler($node);
            $nodeHandler->setNode($node);
            $nodeHandler->cleanPositions();
        }

        $this->cacheProvider->flushAll();

        return $node;
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    public function getNodeSourcesUrls(Node $node): array
    {
        $paths = [];
        $lastUrl = null;
        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            $url = $this->urlGenerator->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                [
                    RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
                ]
            );
            if (null !== $lastUrl && $url === $lastUrl) {
                throw new SameNodeUrlException('NodeSource URL are the same between translations.');
            }
            $paths[$nodeSource->getTranslation()->getLocale()] = $url;
            $this->logger->debug('Redirect '.$nodeSource->getId().' '.$nodeSource->getTranslation()->getLocale().': '.$url);
            $lastUrl = $url;
        }
        return $paths;
    }

    /**
     * @param Node  $node
     * @param array $previousPaths
     * @param bool  $permanently
     */
    public function redirectAll(Node $node, array $previousPaths, bool $permanently = true): void
    {
        if (count($previousPaths) > 0) {
            /** @var NodesSources $nodeSource */
            foreach ($node->getNodeSources() as $nodeSource) {
                if (!empty($previousPaths[$nodeSource->getTranslation()->getLocale()])) {
                    $this->redirect($nodeSource, $previousPaths[$nodeSource->getTranslation()->getLocale()], $permanently);
                }
            }
        }
    }

    /**
     * Warning: this method DOES NOT flush entity manager.
     *
     * @param NodesSources   $nodeSource
     * @param string         $previousPath
     * @param bool           $permanently
     *
     * @return NodesSources
     */
    protected function redirect(NodesSources $nodeSource, string $previousPath, bool $permanently = true): NodesSources
    {
        if (empty($previousPath) || $previousPath === '/') {
            $this->logger->warning('Cannot redirect empty or root path: ' . $nodeSource->getTitle());
            return $nodeSource;
        }

        $newPath = $this->urlGenerator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [
                RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
                NodeRouter::NO_CACHE_PARAMETER => true // do not use nodeSourceUrl cache provider
            ]
        );

        /*
         * Only creates redirection if path changed
         */
        if ($previousPath !== $newPath) {
            /** @var EntityRepository $redirectionRepo */
            $redirectionRepo = $this->managerRegistry->getRepository(Redirection::class);

            /*
             * Checks if new node path is already registered as
             * a redirection --> remove redirection.
             */
            $loopingRedirection = $redirectionRepo->findOneBy([
                'query' => $newPath,
            ]);
            if (null !== $loopingRedirection) {
                $this->getManager()->remove($loopingRedirection);
            }

            $existingRedirection = $redirectionRepo->findOneBy([
                'query' => $previousPath,
            ]);
            if (null === $existingRedirection) {
                $existingRedirection = new Redirection();
                $this->getManager()->persist($existingRedirection);
                $existingRedirection->setQuery($previousPath);
                $this->logger->info('New redirection created', [
                    'oldPath' => $previousPath,
                    'nodeSource' => $nodeSource->getId(),
                ]);
            }
            $existingRedirection->setRedirectNodeSource($nodeSource);
            if ($permanently) {
                $existingRedirection->setType(Response::HTTP_MOVED_PERMANENTLY);
            } else {
                $existingRedirection->setType(Response::HTTP_FOUND);
            }
        }

        return $nodeSource;
    }
}
