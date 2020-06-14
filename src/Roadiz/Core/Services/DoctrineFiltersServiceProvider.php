<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\ANodesFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\BNodesFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\NodesSourcesNodeFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\NodesSourcesNodeTypeFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\NodesSourcesReachableFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\NodeTranslationFilter;
use RZ\Roadiz\Utils\Doctrine\ORM\Filter\NodeTypeFilter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DoctrineFiltersServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Container $c) {
            $dispatcher->addSubscriber(new NodeTypeFilter());
            $dispatcher->addSubscriber(new ANodesFilter());
            $dispatcher->addSubscriber(new BNodesFilter());
            $dispatcher->addSubscriber(new NodesSourcesNodeFilter());
            $dispatcher->addSubscriber(new NodesSourcesNodeTypeFilter());
            $dispatcher->addSubscriber(new NodesSourcesReachableFilter($c['nodeTypesBag']));
            $dispatcher->addSubscriber(new NodeTranslationFilter());

            return $dispatcher;
        });
    }
}
