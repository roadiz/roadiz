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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Utils\StringHandler;

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
     * @ORM\Column(type="string", name="color", unique=false, nullable=true)
     * @var string
     */
    protected $color = '#000000';
    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $name = 'Untitled';
    /**
     * @ORM\Column(name="display_name", type="string")
     * @var string
     */
    private $displayName = 'Untitled';
    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $description;
    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $email;
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @var bool
     */
    private $open = true;
    /**
     * @ORM\Column(name="close_date", type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $closeDate = null;
    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormField", mappedBy="customForm", cascade={"ALL"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    private $fields;
    /**
     * @ORM\OneToMany(
     *    targetEntity="RZ\Roadiz\Core\Entities\CustomFormAnswer",
     *    mappedBy="customForm",
     *    cascade={"ALL"}
     * )
     * @var ArrayCollection
     */
    private $customFormAnswers;
    /**
     * @ORM\OneToMany(targetEntity="NodesCustomForms", mappedBy="customForm", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $nodes = null;

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
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName(string $displayName): CustomForm
    {
        $this->displayName = $displayName;
        $this->setName($displayName);

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): CustomForm
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail(string $email): CustomForm
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param boolean $open
     *
     * @return $this
     */
    public function setOpen(bool $open): CustomForm
    {
        $this->open = $open;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCloseDate(): ?\DateTime
    {
        return $this->closeDate;
    }

    /**
     * @param \DateTime $closeDate
     *
     * @return $this
     */
    public function setCloseDate(\DateTime $closeDate = null): CustomForm
    {
        $this->closeDate = $closeDate;
        return $this;
    }

    /**
     * Combine open flag and closeDate to determine
     * if current form is still available.
     *
     * @return boolean
     */
    public function isFormStillOpen(): bool
    {
        $nowDate = new \DateTime();

        if ($this->closeDate >= $nowDate &&
            $this->open === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the value of color.
     *
     * @return string
     */
    public function getColor(): string
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
    public function setColor(string $color): CustomForm
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsNames(): array
    {
        $namesArray = [];

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getName();
        }

        return $namesArray;
    }

    /**
     * @return Collection
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * Get every node-type fields names in
     * a simple array.
     *
     * @return array
     */
    public function getFieldsLabels(): array
    {
        $namesArray = [];

        foreach ($this->getFields() as $field) {
            $namesArray[] = $field->getLabel();
        }

        return $namesArray;
    }

    /**
     * @param CustomFormField $field
     * @return CustomForm
     */
    public function addField(CustomFormField $field)
    {
        if (!$this->getFields()->contains($field)) {
            $this->getFields()->add($field);
            $field->setCustomForm($this);
        }

        return $this;
    }

    /**
     * @param CustomFormField $field
     * @return CustomForm
     */
    public function removeField(CustomFormField $field): CustomForm
    {
        if ($this->getFields()->contains($field)) {
            $this->getFields()->removeElement($field);
            $field->setCustomForm(null);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCustomFormAnswers(): Collection
    {
        return $this->customFormAnswers;
    }

    /**
     * @return string
     */
    public function getOneLineSummary(): string
    {
        return $this->getId() . " — " . $this->getName() .
            " — Open : " . ($this->isOpen() ? 'true' : 'false') . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): CustomForm
    {
        $this->name = StringHandler::slugify($name);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @return string $text
     */
    public function getFieldsSummary(): string
    {
        $text = "|" . PHP_EOL;
        foreach ($this->getFields() as $field) {
            $text .= "|--- " . $field->getOneLineSummary();
        }

        return $text;
    }

    /**
     * @return Collection
     */
    public function getNodes(): Collection
    {
        return $this->nodes;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $suffix = "-" . uniqid();
            $this->name .= $suffix;
            $this->displayName .= $suffix;
            $this->customFormAnswers = new ArrayCollection();
            $fields = $this->getFields();

            if ($fields !== null) {
                $this->fields = new ArrayCollection();
                /** @var CustomFormField $field */
                foreach ($fields as $field) {
                    $cloneField = clone $field;
                    $this->fields->add($cloneField);
                    $cloneField->setCustomForm($this);
                }
            }
            $this->nodes = new ArrayCollection();
            $this->setCreatedAt(new \DateTime());
            $this->setUpdatedAt(new \DateTime());
        }
    }
}
