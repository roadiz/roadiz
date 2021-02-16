<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\Entities\CustomForm;

/**
 * Handle operations with node-type entities.
 */
class CustomFormHandler extends AbstractHandler
{
    protected ?CustomForm $customForm = null;

    public function getCustomForm(): ?CustomForm
    {
        return $this->customForm;
    }

    /**
     * @param CustomForm $customForm
     * @return $this
     */
    public function setCustomForm(CustomForm $customForm)
    {
        $this->customForm = $customForm;
        return $this;
    }

    /**
     * Reset current node-type fields positions.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** field
     */
    public function cleanFieldsPositions(bool $setPositions = true): float
    {
        if (null === $this->customForm) {
            throw new \BadMethodCallException('CustomForm is null');
        }

        $criteria = Criteria::create();
        $criteria->orderBy(['position' => 'ASC']);
        $fields = $this->customForm->getFields()->matching($criteria);
        $i = 1;
        foreach ($fields as $field) {
            if ($setPositions) {
                $field->setPosition($i);
            }
            $i++;
        }

        if ($setPositions) {
            $this->objectManager->flush();
        }

        return $i;
    }
}
