<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CustomFormField.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="custom_form_field_attributes")
 * @HasLifecycleCallbacks
 */
class CustomFormFieldAttribute extends AbstractEntity
{

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomFormAnswer", inversedBy="answerField")
     * @JoinColumn(name="custom_form_answer_id", referencedColumnName="id", onDelete="CASCADE")
     */

    private $customFormAnswer;

    public function setCustomFormAnswer($customFormAnswer)
    {
        $this->customFormAnswer = $customFormAnswer;
        return $this;
    }

    public function getCustomFormAnswer()
    {
        return $this->customFormAnswer;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomFormField", inversedBy="customFormFieldAttribute")
     * @JoinColumn(name="custom_form_field_id", referencedColumnName="id")
     */
    private $customFormField;

    public function setCustomFormField($customFormField)
    {
        $this->customFormField = $customFormField;
        return $this;
    }

    public function getCustomFormField()
    {
        return $this->customFormField;
    }

    /**
     * @Column(type="string")
     */
    private $value;

    /**
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

}