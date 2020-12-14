<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Pimple\Container;
use RZ\Roadiz\Config\Configuration;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;

/**
 * @package RZ\Roadiz\Core\Events
 */
class NodesSourcesInheritanceSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        // the $metadata is all the mapping info for this class
        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();
        // the annotation reader accepts a ReflectionClass, which can be
        // obtained from the $metadata
        $class = $metadata->getReflectionClass();

        if ($class->getName() === NodesSources::class) {
            try {
                // List node types
                /** @var NodeType[] $nodeTypes */
                $nodeTypes = $this->container->offsetGet('nodeTypesBag')->all();
                $map = [];
                foreach ($nodeTypes as $type) {
                    $map[strtolower($type->getName())] = $type->getSourceEntityFullQualifiedClassName();
                }

                $metadata->setDiscriminatorMap($map);

                /*
                 * change here your inheritance type according to configuration
                 */
                $inheritanceType = $this->container['config']['inheritance']['type'];
                if ($inheritanceType === Configuration::INHERITANCE_TYPE_JOINED) {
                    $metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_JOINED);
                } elseif ($inheritanceType === Configuration::INHERITANCE_TYPE_SINGLE_TABLE) {
                    $metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
                }
            } catch (\Exception $e) {
                /*
                 * Database tables don't exist yet
                 * Need Install
                 */
            }
        }
    }
}
