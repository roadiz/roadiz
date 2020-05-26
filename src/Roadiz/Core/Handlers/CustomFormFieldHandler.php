<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ObjectManager;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\CustomFormField;

/**
 * Handle operations with customForms fields entities.
 */
class CustomFormFieldHandler extends AbstractHandler
{
    /** @var null|CustomFormField  */
    private $customFormField = null;

    /** @var \Pimple\Container  */
    private $container;

    /**
     * @return CustomFormField
     */
    public function getCustomFormField()
    {
        return $this->customFormField;
    }
    /**
     * @param CustomFormField $customFormField
     *
     * @return $this
     */
    public function setCustomFormField(CustomFormField $customFormField)
    {
        $this->customFormField = $customFormField;
        return $this;
    }

    /**
     * Create a new custom-form-field handler with custom-form-field to handle.
     *
     * @param ObjectManager $objectManager
     * @param Container $container
     */
    public function __construct(ObjectManager $objectManager, Container $container)
    {
        parent::__construct($objectManager);
        $this->container = $container;
    }

    /**
     * Clean position for current customForm siblings.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** customFormField
     */
    public function cleanPositions($setPositions = true)
    {
        if ($this->customFormField->getCustomForm() !== null) {
            /** @var CustomFormHandler $customFormHandler */
            $customFormHandler = $this->container['custom_form.handler'];
            $customFormHandler->setCustomForm($this->customFormField->getCustomForm());
            return $customFormHandler->cleanFieldsPositions($setPositions);
        }

        return 1;
    }
}
