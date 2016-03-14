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

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Handle operations with node-type entities.
 */
class NodeTypeHandler
{
    private $nodeType = null;

    /**
     * @return \RZ\Roadiz\Core\Entities\NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * Create a new node-type handler with node-type to handle.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeType $nodeType
     */
    public function __construct(NodeType $nodeType)
    {
        $this->nodeType = $nodeType;
    }

    /**
     * Remove node type entity class file from server.
     *
     * @return boolean
     */
    public function removeSourceEntityClass()
    {
        $folder = ROADIZ_ROOT.'/gen-src/'.NodeType::getGeneratedEntitiesNamespace();
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
        $folder = ROADIZ_ROOT.'/gen-src/'.NodeType::getGeneratedEntitiesNamespace();
        $file = $folder.'/'.$this->nodeType->getSourceEntityClassName().'.php';

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (!file_exists($file)) {
            $fields = $this->nodeType->getFields();
            $fieldsArray = [];
            $indexes = [];
            foreach ($fields as $field) {
                $fieldsArray[] = $field->getHandler()->generateSourceField();
                if ($field->isIndexed()) {
                    $indexes[] = $field->getHandler()->generateSourceFieldIndex();
                }
            }

            $content = '<?php
/*
 * THIS IS A GENERATED FILE, DO NOT EDIT IT
 * IT WILL BE RECREATED AT EACH NODE-TYPE UPDATE
 */
namespace '.NodeType::getGeneratedEntitiesNamespace().';

use RZ\Roadiz\Core\Entities\NodesSources;
use Doctrine\ORM\Mapping as ORM;

/**
 * Generated custom node-source type from RZ-CMS backoffice.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesRepository")
 * @ORM\Table(name="'.$this->nodeType->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */
class '.$this->nodeType->getSourceEntityClassName().' extends NodesSources
{
    '.implode('', $fieldsArray).'

    public function __toString()
    {
        return \''.$this->nodeType->getSourceEntityClassName().' #\' . $this->getId() .
        \' <\' . $this->getTitle() . \'>[\' . $this->getTranslation()->getLocale() .
        \']\';
    }
}
';
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
            new DoctrineCacheClearer(Kernel::getService('em')),
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
        $nodes = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->findBy([
                'nodeType' => $this->getNodeType()
            ]);

        foreach ($nodes as $node) {
            $node->getHandler()->removeWithChildrenAndAssociations();
        }

        /*
         * Remove class and database table
         */
        $this->deleteSchema();

        /*
         * Remove node type
         */
        Kernel::getService('em')->remove($this->getNodeType());
        Kernel::getService('em')->flush();

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
             * options
             */
            if ("" != $newNodeType->getDisplayName()) {
                $this->nodeType->setDisplayName($newNodeType->getDisplayName());
            }
            if ("" != $newNodeType->getDescription()) {
                $this->nodeType->setDescription($newNodeType->getDescription());
            }
            /*
             * make fields diff
             */
            $existingFieldsNames = $this->nodeType->getFieldsNames();

            foreach ($newNodeType->getFields() as $newField) {
                if (false === in_array($newField->getName(), $existingFieldsNames)) {
                    /*
                     * Field does not exist in type,
                     * creating it.
                     */
                    $newField->setNodeType($this->nodeType);
                    Kernel::getService('em')->persist($newField);
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

        Kernel::getService('em')->flush();

        return $i;
    }
}
