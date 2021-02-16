<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\CustomFormField;

/**
 * Handle operations with customForms fields entities.
 */
class CustomFormFieldHandler extends AbstractHandler
{
    private ?CustomFormField $customFormField = null;
    private CustomFormHandler $customFormHandler;

    /**
     * @return CustomFormField
     */
    public function getCustomFormField(): ?CustomFormField
    {
        return $this->customFormField;
    }
    /**
     * @param CustomFormField $customFormField
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
     * @param CustomFormHandler $customFormHandler
     */
    public function __construct(ObjectManager $objectManager, CustomFormHandler $customFormHandler)
    {
        parent::__construct($objectManager);
        $this->customFormHandler = $customFormHandler;
    }

    /**
     * Clean position for current customForm siblings.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** customFormField
     */
    public function cleanPositions(bool $setPositions = true): float
    {
        if (null === $this->customFormField) {
            throw new \BadMethodCallException('CustomForm is null');
        }

        if ($this->customFormField->getCustomForm() !== null) {
            $this->customFormHandler->setCustomForm($this->customFormField->getCustomForm());
            return $this->customFormHandler->cleanFieldsPositions($setPositions);
        }

        return 1;
    }
}
