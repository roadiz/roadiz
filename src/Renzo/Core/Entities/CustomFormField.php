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

use RZ\Renzo\Core\AbstractEntities\AbstractField;
use RZ\Renzo\Core\Handlers\CustomFormFieldHandler;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="custom_form_fields",
 * uniqueConstraints={@UniqueConstraint(columns={"name", "custom_form_id"})})
 * @HasLifecycleCallbacks
 */
class CustomFormField extends AbstractField
{
    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomForm", inversedBy="fields")
     * @JoinColumn(name="custom_form_id", onDelete="CASCADE")
     */
    private $customForm;

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
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\CustomFormFieldAttribute", mappedBy="customFormField")
     */
    private $customFormFieldAttribute;

    public function getCustomFormFieldAttribute()
    {
        return $this->customFormFieldAttribute;
    }

    public function __contruct()
    {
        $this->customFormFieldAttribute = new ArrayCollection();
    }

    /**
     * @Column(name="field_required", type="boolean")
     */
    private $required = false;

    /**
     * @return boolean $isRequired
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return  RZ\Renzo\Core\Handlers\CustomFormFieldHandler
     */
    public function getHandler()
    {
        return new CustomFormFieldHandler($this);
    }

    /**
     * @PrePersist
     */
    public function prePersist()
    {
        /*
         * Get the last index after last node in parent
         */
        $this->setPosition($this->getHandler()->cleanPositions());
    }

    /**
     * @todo Move this method to a CustomFormFieldViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLabel().PHP_EOL;
    }

}
