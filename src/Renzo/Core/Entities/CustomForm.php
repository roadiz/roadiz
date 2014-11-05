<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file CustomForm.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;
use RZ\Renzo\Core\Handlers\CustomFormHandler;
use RZ\Renzo\Core\Serializers\CustomFormSerializer;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * CustomForms describe each node structure family,
 * They are mandatory before creating any Node.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\CustomFormRepository")
 * @Table(name="custom_forms")
 * @HasLifecycleCallbacks
 */
class CustomForm extends AbstractDateTimed
{
    /**
     * @Column(type="string", unique=true)
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = StringHandler::classify($name);

        return $this;
    }

    /**
     * @Column(name="display_name", type="string")
     */
    private $displayName;
    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }
    /**
     * @param string $displayName
     *
     * @return $this
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Column(type="boolean")
     */
    private $open = true;
    /**
     * @return boolean
     */
    public function isOpen()
    {
        return $this->open;
    }
    /**
     * @param boolean $open
     *
     * @return $this
     */
    public function setOpen($open)
    {
        $this->open = $open;

        return $this;
    }

    /**
     * @Column(name="close_date", type="datetime")
     */
    private $closeDate = null;
    /**
     * @return datetime
     */
    public function getCloseDate()
    {
        return $this->closeDate;
    }
    /**
     * @param datetime $closeDate
     *
     * @return $this
     */
    public function setCloseDate($closeDate)
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    /**
     * @Column(type="string", name="color", unique=false, nullable=true)
     */
    protected $color = '#000000';

    /**
     * Gets the value of color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Sets the value of color.
     *
     * @param string $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="CustomFormField", mappedBy="customForm", cascade={"ALL"})
     * @OrderBy({"position" = "ASC"})
     */
    private $fields;

    /**
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }
    /**
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsNames()
    {
        $namesArray = array();

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getName();
        }

        return $namesArray;
    }

    /**
     * @param CustomFormField $field
     *
     * @return CustomFormField
     */
    public function addField(CustomFormField $field)
    {
        if (!$this->getFields()->contains($field)) {
            $this->getFields()->add($field);
        }

        return $this;
    }

    /**
     * @param CustomFormField $field
     *
     * @return CustomFormField
     */
    public function removeField(CustomFormField $field)
    {
        if ($this->getFields()->contains($field)) {
            $this->getFields()->removeElement($field);
        }

        return $this;
    }

   /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\CustomFormAnswer",
     *            mappedBy="customForm")
     **/

   private $customFormAnswers;

   public function getCustomForAnswers()
   {
        return $this->customFormAnswers;
   }

   public function getHandler()
   {
        return new CustomFormHandler($this);
   }

    /**
     * Create a new CustomForm.
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->customFormAnswers = new ArrayCollection();
    }


    /**
     * @todo Move this method to a CustomFormViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName().
            " — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
    }

    /**
     * @todo Move this method to a CustomFormViewer
     * @return string $text
     */
    public function getFieldsSummary()
    {
        $text = "|".PHP_EOL;
        foreach ($this->getFields() as $field) {
            $text .= "|--- ".$field->getOneLineSummary();
        }

        return $text;
    }

}
