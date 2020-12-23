<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Pimple\Container;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

class HandlerFactory implements HandlerFactoryInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param AbstractEntity $entity
     * @return AbstractHandler
     */
    public function getHandler(AbstractEntity $entity)
    {
        switch (true) {
            case ($entity instanceof Node):
                return $this->container['node.handler']->setNode($entity);
            case ($entity instanceof NodesSources):
                return $this->container['nodes_sources.handler']->setNodeSource($entity);
            case ($entity instanceof NodeType):
                return $this->container['node_type.handler']->setNodeType($entity);
            case ($entity instanceof NodeTypeField):
                return $this->container['node_type_field.handler']->setNodeTypeField($entity);
            case ($entity instanceof Document):
                return $this->container['document.handler']->setDocument($entity);
            case ($entity instanceof CustomForm):
                return $this->container['custom_form.handler']->setCustomForm($entity);
            case ($entity instanceof CustomFormField):
                return $this->container['custom_form_field.handler']->setCustomFormField($entity);
            case ($entity instanceof Folder):
                return $this->container['folder.handler']->setFolder($entity);
            case ($entity instanceof Font):
                return $this->container['font.handler']->setFont($entity);
            case ($entity instanceof Group):
                return $this->container['group.handler']->setGroup($entity);
            case ($entity instanceof Tag):
                return $this->container['tag.handler']->setTag($entity);
            case ($entity instanceof Translation):
                return $this->container['translation.handler']->setTranslation($entity);
        }

        throw new \InvalidArgumentException('HandlerFactory does not support ' . get_class($entity));
    }
}
