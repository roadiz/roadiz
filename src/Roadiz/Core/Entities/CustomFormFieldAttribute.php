<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="custom_form_field_attributes")
 * @ORM\HasLifecycleCallbacks
 */
class CustomFormFieldAttribute extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomFormAnswer", inversedBy="answerFields")
     * @ORM\JoinColumn(name="custom_form_answer_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomFormAnswer
     */
    protected $customFormAnswer;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomFormField", inversedBy="customFormFieldAttributes")
     * @ORM\JoinColumn(name="custom_form_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomFormField
     */
    protected $customFormField;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    protected $value = null;

    /**
     * @return string $value
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(?string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value of customFormAnswer.
     *
     * @return CustomFormAnswer
     */
    public function getCustomFormAnswer()
    {
        return $this->customFormAnswer;
    }

    /**
     * Sets the value of customFormAnswer.
     *
     * @param CustomFormAnswer $customFormAnswer the custom form answer
     *
     * @return self
     */
    public function setCustomFormAnswer(CustomFormAnswer $customFormAnswer)
    {
        $this->customFormAnswer = $customFormAnswer;

        return $this;
    }

    /**
     * Gets the value of customFormField.
     *
     * @return CustomFormField
     */
    public function getCustomFormField()
    {
        return $this->customFormField;
    }

    /**
     * Sets the value of customFormField.
     *
     * @param CustomFormField $customFormField the custom form field
     *
     * @return self
     */
    public function setCustomFormField(CustomFormField $customFormField)
    {
        $this->customFormField = $customFormField;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}
