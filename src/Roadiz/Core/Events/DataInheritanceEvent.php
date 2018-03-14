<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file DataInheritanceEvent.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;

/**
 * DataInheritanceEvent
 */
class DataInheritanceEvent
{
    protected $tablesPrefix;
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     * @param string $tablesPrefix
     */
    public function __construct(Container $container, $tablesPrefix = '')
    {
        $this->tablesPrefix = $tablesPrefix;
        $this->container = $container;
    }

    /**
     * @param \Doctrine\ORM\Event\LoadClassMetadataEventArgs  $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        // the $metadata is all the mapping info for this class
        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        /*
         * Prefix tables
         */
        if (!empty($this->tablesPrefix)) {
            $metadata->table['name'] = $this->tablesPrefix.'_'.$metadata->table['name'];

            /*
             * Prefix join tables
             */
            foreach ($metadata->associationMappings as $key => $association) {
                if (!empty($association['joinTable']['name'])) {
                    $metadata->associationMappings[$key]['joinTable']['name'] =
                        $this->tablesPrefix.'_'.$association['joinTable']['name'];
                }
            }
        }

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
                    $map[strtolower($type->getName())] = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();
                }

                $metadata->setDiscriminatorMap($map);
            } catch (\Exception $e) {
                /*
                 * Database tables don't exist yet
                 * Need Install
                 */
            }
        }
    }
}
