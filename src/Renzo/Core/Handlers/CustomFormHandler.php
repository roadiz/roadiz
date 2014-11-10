<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CustomFormHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\CustomForm;
use RZ\Renzo\Core\Entities\CustomFormField;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\DBAL\Schema\Column;

/**
 * Handle operations with node-type entities.
 */
class CustomFormHandler
{
    private $customForm = null;

    /**
     * @return RZ\Renzo\Core\Entities\CustomForm
     */
    public function getCustomForm()
    {
        return $this->customForm;
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     *
     * @return $this
     */
    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * Create a new node-type handler with node-type to handle.
     *
     * @param RZ\Renzo\Core\Entities\CustomForm $customForm
     */
    public function __construct(CustomForm $customForm)
    {
        $this->customForm = $customForm;
    }

    /**
     * Reset current node-type fields positions.
     *
     * @return int Return the next position after the **last** field
     */
    public function cleanFieldsPositions()
    {
        $fields = $this->customForm->getFields();
        $i = 1;
        foreach ($fields as $field) {
            $field->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }
}
