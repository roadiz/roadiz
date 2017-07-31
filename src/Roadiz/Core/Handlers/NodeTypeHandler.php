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
 * @file NodeTypeHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Doctrine\Generators\EntityGenerator;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Handle operations with node-type entities.
 */
class NodeTypeHandler extends AbstractHandler
{
    private $nodeType;

    /** @var Kernel  */
    protected $kernel;

    /**
     * @return NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param NodeType $nodeType
     * @return $this
     */
    public function setNodeType(NodeType $nodeType)
    {
        $this->nodeType = $nodeType;
        return $this;
    }

    /**
     * Create a new node-type handler with node-type to handle.
     *
     * @param NodeType|null $nodeType
     */
    public function __construct(NodeType $nodeType = null)
    {
        parent::__construct();
        $this->nodeType = $nodeType;
        $this->kernel = Kernel::getInstance();
    }

    /**
     * @return string
     */
    public function getGeneratedEntitiesFolder()
    {
        return $this->kernel->getRootDir() . '/gen-src/' . NodeType::getGeneratedEntitiesNamespace();
    }

    /**
     * Remove node type entity class file from server.
     *
     * @return boolean
     */
    public function removeSourceEntityClass()
    {
        $folder = $this->getGeneratedEntitiesFolder();
        $file = $folder.'/'.$this->nodeType->getSourceEntityClassName().'.php';

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * Generate Doctrine entity class for current nodetype.
     *
     * @return string or false
     */
    public function generateSourceEntityClass()
    {
        $folder = $this->getGeneratedEntitiesFolder();
        $file = $folder.'/'.$this->nodeType->getSourceEntityClassName().'.php';

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (!file_exists($file)) {
            $classGenerator = new EntityGenerator($this->nodeType);
            $content = $classGenerator->getClassContent();

            if (false === @file_put_contents($file, $content)) {
                throw new IOException("Impossible to write entity class file (".$file.").", 1);
            }

            /*
             * Force Zend OPcache to reset file
             */
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file, true);
            }

            return "Source class “".$this->nodeType->getSourceEntityClassName()."” has been created.".PHP_EOL;
        } else {
            return "Source class “".$this->nodeType->getSourceEntityClassName()."” already exists.".PHP_EOL;
        }
    }

    /**
     * Clear doctrine metadata cache and
     * regenerate entity class file.
     *
     * @return $this
     */
    public function updateSchema()
    {
        $this->clearCaches();
        $this->regenerateEntityClass();

        return $this;
    }

    /**
     * Delete and recreate entity class file.
     */
    public function regenerateEntityClass()
    {
        $this->removeSourceEntityClass();
        $this->generateSourceEntityClass();

        return $this;
    }

    /**
     * Delete node-type class from database.
     *
     * @return $this
     */
    public function deleteSchema()
    {
        $this->removeSourceEntityClass();
        $this->clearCaches();

        return $this;
    }

    protected function clearCaches()
    {
        $clearers = [
            new DoctrineCacheClearer($this->entityManager, $this->kernel),
            new OPCacheClearer(),
        ];
        foreach ($clearers as $clearer) {
            $clearer->clear();
        }
    }

    /**
     * Delete node-type inherited nodes and its database schema
     * before removing it from node-types table.
     *
     * @return $this
     */
    public function deleteWithAssociations()
    {
        /*
         * Delete every nodes
         */
        $nodes = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->setDisplayingNotPublishedNodes(true)
            ->findBy([
                'nodeType' => $this->getNodeType()
            ]);

        /** @var Node $node */
        foreach ($nodes as $node) {
            /** @var NodeHandler $nodeHandler */
            $nodeHandler = $this->kernel->get('node.handler');
            $nodeHandler->setNode($node);
            $nodeHandler->removeWithChildrenAndAssociations();
        }

        /*
         * Remove class and database table
         */
        $this->deleteSchema();

        /*
         * Remove node type
         */
        $this->entityManager->remove($this->getNodeType());
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Update current node-type using a new one.
     *
     * Update diff will update only non-critical fields such as :
     *
     * * description
     * * displayName
     *
     * It will only create absent node-type fields won't delete fields
     * not to lose any data.
     *
     * This method does not flush ORM. You'll need to manually call it.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeType $newNodeType
     *
     * @throws \RuntimeException If newNodeType param is null
     */
    public function diff(NodeType $newNodeType)
    {
        if (null !== $newNodeType) {
            /*
             * Override display name
             */
            if ("" != $newNodeType->getDisplayName()) {
                $this->nodeType->setDisplayName($newNodeType->getDisplayName());
            }
            /*
             * Override description
             */
            if ("" != $newNodeType->getDescription()) {
                $this->nodeType->setDescription($newNodeType->getDescription());
            }
            /*
             * Override color
             */
            if ("" != $newNodeType->getColor()) {
                $this->nodeType->setColor($newNodeType->getColor());
            }
            /*
             * Override booleans
             */
            $this->nodeType->setVisible($newNodeType->isVisible());
            $this->nodeType->setHidingNodes($newNodeType->isHidingNodes());
            $this->nodeType->setNewsletterType($newNodeType->isNewsletterType());

            /*
             * make fields diff
             */
            $existingFieldsNames = $this->nodeType->getFieldsNames();

            /** @var NodeTypeField $newField */
            foreach ($newNodeType->getFields() as $newField) {
                if (false === in_array($newField->getName(), $existingFieldsNames)) {
                    /*
                     * Field does not exist in type,
                     * creating it.
                     */
                    $newField->setNodeType($this->nodeType);
                    $this->entityManager->persist($newField);
                } else {
                    /*
                     * Field already exists.
                     * Updating it.
                     */
                    /** @var NodeTypeField $oldField */
                    $oldField = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                        ->findOneBy([
                            'nodeType' => $this->nodeType,
                            'name' => $newField->getName(),
                        ]);
                    if (null !== $oldField) {
                        $oldField->setVisible($newField->isVisible());
                        $oldField->setIndexed($newField->isIndexed());
                        $oldField->setUniversal($newField->isUniversal());
                        $oldField->setDefaultValues($newField->getDefaultValues());
                        $oldField->setDescription($newField->getDescription());
                        $oldField->setLabel($newField->getLabel());
                    }
                }
            }
        } else {
            throw new \RuntimeException("New node-type is null", 1);
        }
    }

    /**
     * Reset current node-type fields positions.
     *
     * @return int Return the next position after the **last** field
     */
    public function cleanFieldsPositions()
    {
        $fields = $this->nodeType->getFields();
        $i = 1;
        foreach ($fields as $field) {
            $field->setPosition($i);
            $i++;
        }

        $this->entityManager->flush();

        return $i;
    }
}
