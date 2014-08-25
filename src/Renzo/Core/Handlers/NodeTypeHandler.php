<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\DBAL\Schema\Column;
/**
 * Handle operations with node-type entities.
 */
class NodeTypeHandler
{
    private $nodeType = null;

    /**
     * @return RZ\Renzo\Core\Entities\NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
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
     * @param NodeType $nodeType
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
        $folder = RENZO_ROOT.'/sources/'.NodeType::getGeneratedEntitiesNamespace();
        $file = $folder.'/'.$this->getNodeType()->getSourceEntityClassName().'.php';

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
        $folder = RENZO_ROOT.'/sources/'.NodeType::getGeneratedEntitiesNamespace();
        $file = $folder.'/'.$this->getNodeType()->getSourceEntityClassName().'.php';

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (!file_exists($file)) {

            $fields = $this->getNodeType()->getFields();
            $fieldsArray = array();
            $indexes = array();
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

use RZ\Renzo\Core\Entities\NodesSources;
/**
 * Generated custom node-source type from RZ-CMS backoffice.
 *
 * @Entity
 * @Table(name="'.$this->getNodeType()->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */
class '.$this->getNodeType()->getSourceEntityClassName().' extends NodesSources
{
    '.implode('', $fieldsArray).'
}';
            file_put_contents($file, $content);

            return "Source class “".$this->getNodeType()->getSourceEntityClassName()."” has been created.".PHP_EOL;
        } else {
            return "Source class “".$this->getNodeType()->getSourceEntityClassName()."” already exists.".PHP_EOL;
        }

        return false;
    }

    /**
     * Update database schema for current node-type.
     *
     * @return $this
     */
    public function updateSchema()
    {
        \RZ\Renzo\Console\SchemaCommand::refreshMetadata();
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
        \RZ\Renzo\Console\SchemaCommand::refreshMetadata();

        return $this;
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
        $nodes = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Node')
            ->findBy(array(
                'nodeType' => $this->getNodeType()
            ));

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
        Kernel::getInstance()->em()->remove($this->getNodeType());
        Kernel::getInstance()->em()->flush();

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
     * @param  RZ\Renzo\Core\Entities\NodeType $newNodeType
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
                $this->getNodeType()->setDisplayName($newNodeType->getDisplayName());
            }
            if ("" != $newNodeType->getDescription()) {
                $this->getNodeType()->setDescription($newNodeType->getDescription());
            }
            /*
             * make fields diff
             */
            $existingFieldsNames = $this->getNodeType()->getFieldsNames();

            foreach ($newNodeType->getFields() as $newField) {
                if (false == in_array($newField->getName(), $existingFieldsNames)) {
                    /*
                     * Field does not exist in type,
                     * creating it.
                     */
                    $newField->setNodeType($this->getNodeType());
                    Kernel::getInstance()->em()->persist($newField);
                }
            }

        } else {
            throw new \RuntimeException("New node-type is null", 1);
        }
    }
}