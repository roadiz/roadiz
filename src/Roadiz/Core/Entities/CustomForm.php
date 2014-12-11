<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file CustomForm.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Core\Handlers\CustomFormHandler;
use RZ\Roadiz\Core\Utils\StringHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * CustomForms describe each node structure family,
 * They are mandatory before creating any Node.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\CustomFormRepository")
 * @ORM\Table(name="custom_forms")
 * @ORM\HasLifecycleCallbacks
 */
class CustomForm extends AbstractDateTimed
{
    /**
     * @ORM\Column(type="string", unique=true)
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
        $this->name = StringHandler::slugify($name);

        return $this;
    }

    /**
     * @ORM\Column(name="display_name", type="string")
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
     * @ORM\Column(type="text", nullable=true)
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $email;
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(name="close_date", type="datetime")
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
     * @ORM\Column(type="string", name="color", unique=false, nullable=true)
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
     * @ORM\OneToMany(targetEntity="CustomFormField", mappedBy="customForm", cascade={"ALL"})
     * @ORM\OrderBy({"position" = "ASC"})
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
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsLabels()
    {
        $namesArray = array();

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getLabel();
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
     * @ORM\OneToMany(
     *    targetEntity="RZ\Roadiz\Core\Entities\CustomFormAnswer",
     *    mappedBy="customForm",
     *    cascade={"ALL"}
     * )
     */
    private $customFormAnswers;

    public function getCustomFormAnswers()
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
        $this->nodes = new ArrayCollection();
    }


    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName().
            " — Open : ".($this->isOpen()?'true':'false').PHP_EOL;
    }

    /**
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

    /**
     * @ORM\OneToMany(targetEntity="NodesCustomForms", mappedBy="customForm", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $nodes = null;
    /**
     * @return ArrayCollection
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
