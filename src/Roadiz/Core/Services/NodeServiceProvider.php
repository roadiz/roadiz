<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesCustomForms;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;
use RZ\Roadiz\Core\Routing\NodesSourcesPathAggregator;
use RZ\Roadiz\Core\Routing\OptimizedNodesSourcesGraphPathAggregator;
use RZ\Roadiz\Utils\Doctrine\Generators\AbstractFieldGenerator;
use RZ\Roadiz\Utils\Doctrine\Generators\EntityGeneratorFactory;
use RZ\Roadiz\Utils\Node\NodeMover;
use RZ\Roadiz\Utils\Node\NodeTranstyper;

class NodeServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container)
    {
        $container[EntityGeneratorFactory::class] = function (Container $c) {
            return new EntityGeneratorFactory($c['nodeTypesBag'], [
                'parent_class' => NodesSources::class,
                'repository_class' => NodesSourcesRepository::class,
                'node_class' => Node::class,
                'document_class' => Document::class,
                'document_proxy_class' => NodesSourcesDocuments::class,
                'custom_form_class' => CustomForm::class,
                'custom_form_proxy_class' => NodesCustomForms::class,
                'translation_class' => Translation::class,
                'namespace' => NodeType::getGeneratedEntitiesNamespace(),
                'use_native_json' => $c['settingsBag']->get(AbstractFieldGenerator::USE_NATIVE_JSON, false)
            ]);
        };

        $container[NodesSourcesPathAggregator::class] = function (Container $c) {
            /*
             * You can override this service to change NS path aggregator strategy
             */
            // return new NodesSourcesGraphPathAggregator();
            return new OptimizedNodesSourcesGraphPathAggregator($c['em']);
        };

        $container[NodeTranstyper::class] = function (Container $c) {
            return new NodeTranstyper($c['em'], $c['logger.doctrine']);
        };

        $container[NodeMover::class] = function (Container $c) {
            return new NodeMover(
                $c['em'],
                $c['router'],
                $c['factory.handler'],
                $c['dispatcher'],
                $c['nodesSourcesUrlCacheProvider'],
                $c['logger.doctrine']
            );
        };
    }
}
