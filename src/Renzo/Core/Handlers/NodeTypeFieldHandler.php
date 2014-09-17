<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeFieldHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

/**
 * Handle operations with node-type fields entities.
 */
class NodeTypeFieldHandler
{

    private $nodeTypeField = null;
    /**
     * @return NodeTypeField
     */
    public function getNodeTypeField()
    {
        return $this->nodeTypeField;
    }
    /**
     * @param NodeTypeField $nodeTypeField
     *
     * @return $this
     */
    public function setNodeTypeField($nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;

        return $this;
    }

    /**
     * Generate PHP annotation block for table indexes.
     *
     * @return string
     */
    public function generateSourceFieldIndex()
    {
        if (NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()] !== null) {
            return '@Index(name="'.$this->getNodeTypeField()->getName().'_idx", columns={"'.$this->getNodeTypeField()->getName().'"})';
        } else {
            return '';
        }
    }

    /**
     * Generate PHP code for current node-type field.
     *
     * @return string
     */
    public function generateSourceField()
    {
        if (NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()] !== null) {
            $var = 'private $'.$this->getNodeTypeField()->getName().';';
            if ($this->getNodeTypeField()->getType() === NodeTypeField::BOOLEAN_T) {
                $var = 'private $'.$this->getNodeTypeField()->getName().' = false;';
            }
            if ($this->getNodeTypeField()->getType() === NodeTypeField::INTEGER_T) {
                $var = 'private $'.$this->getNodeTypeField()->getName().' = 0;';
            }

            return '
    /**
     * @Column(type="'.NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()].'", nullable=true )
     */
    '.$var.'
    /**
     * @return mixed
     */
    public function '.$this->getNodeTypeField()->getGetterName().'()
    {
        return $this->'.$this->getNodeTypeField()->getName().';
    }
    /**
     * @param mixed $'.$this->getNodeTypeField()->getName().'
     *
     * @return $this
     */
    public function '.$this->getNodeTypeField()->getSetterName().'($'.$this->getNodeTypeField()->getName().')
    {
        $this->'.$this->getNodeTypeField()->getName().' = $'.$this->getNodeTypeField()->getName().';

        return $this;
    }'.PHP_EOL;

        }

        return '';
    }

    /**
     * Create a new node-type-field handler with node-type-field to handle.
     *
     * @param NodeTypeField $field
     */
    public function __construct(NodeTypeField $field)
    {
        $this->nodeTypeField = $field;
    }
}
