<?php
declare(strict_types=1);

namespace RZ\Roadiz\EntityGenerator;

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
use RZ\Roadiz\EntityGenerator\Field\AbstractFieldGenerator;

class EntityGeneratorServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple[EntityGeneratorFactory::class] = function (Container $c) {
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
    }
}
