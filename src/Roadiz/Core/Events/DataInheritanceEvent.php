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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\NodeType;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * DataInheritanceEvent
 */
class DataInheritanceEvent
{
    /**
     * @param Doctrine\ORM\Event\LoadClassMetadataEventArgs  $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {

        // the $metadata is all the mapping info for this class
        $metadata = $eventArgs->getClassMetadata();

        /*
         * Prefix tables
         */
        if (!empty(Kernel::getService('config')['doctrine']['prefix'])) {
            $metadata->table['name'] = Kernel::getService('config')['doctrine']['prefix'].'_'.$metadata->table['name'];

            /*
             * Prefix join tables
             */
            foreach ($metadata->associationMappings as $key => $association) {
                if (!empty($association['joinTable']['name'])) {
                    $metadata->associationMappings[$key]['joinTable']['name'] =
                        Kernel::getService('config')['doctrine']['prefix'].'_'.$association['joinTable']['name'];
                }
            }
        }

        // the annotation reader accepts a ReflectionClass, which can be
        // obtained from the $metadata
        $class = $metadata->getReflectionClass();

        if ($class->getName() === 'RZ\Roadiz\Core\Entities\NodesSources') {

            try {
                // List node types
                $nodeTypes = Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                    ->findAll();

                $map = array();
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

    /**
     * Get NodesSources class metadata.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public static function getNodesSourcesMetadata()
    {
        $metadata = new ClassMetadata('RZ\Roadiz\Core\Entities\NodesSources');

        try {
            /**
             *  List node types
             */
            $nodeTypes = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findAll();

            $map = array();
            foreach ($nodeTypes as $type) {
                $map[strtolower($type->getName())] = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();
            }

            $metadata->setDiscriminatorMap($map);

            return $metadata;
        } catch (\PDOException $e) {
            /*
             * Database tables don't exist yet
             * Need Install
             */

            return null;
        }
    }
}
