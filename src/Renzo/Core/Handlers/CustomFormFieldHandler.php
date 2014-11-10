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
use RZ\Renzo\Core\Entities\CustomFormField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

/**
 * Handle operations with node-type fields entities.
 */
class CustomFormFieldHandler
{

    private $customFormField = null;
    /**
     * @return CustomFormField
     */
    public function getCustomFormField()
    {
        return $this->customFormField;
    }
    /**
     * @param CustomFormeField $customFormField
     *
     * @return $this
     */
    public function setCustomFormField($customFormField)
    {
        $this->customFormField = $customFormField;

        return $this;
    }

    protected function getDecimalPrecision()
    {
        if ($this->customFormField->getType() == CustomFormField::DECIMAL_T) {
            return 'precision=10, scale=3, ';
        } else {
            return '';
        }
    }

    /**
     * Create a new custom-form-field handler with custom-form-field to handle.
     *
     * @param CustomFormField $field
     */
    public function __construct(CustomFormField $field)
    {
        $this->customFormField = $field;
    }

    /**
     * Clean position for current customForm siblings.
     *
     * @return int Return the next position after the **last** customFormField
     */
    public function cleanPositions()
    {
        if ($this->customFormField->getCustomForm() !== null) {
            return $this->customFormField->getCustomForm()->getHandler()->cleanFieldsPositions();
        }
    }
}
