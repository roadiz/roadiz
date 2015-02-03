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
 * @file CustomFormField.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="custom_form_fields", uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"name", "custom_form_id"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class CustomFormField extends AbstractField
{
    /**
     * Associates custom form field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = [
        AbstractField::STRING_T =>   'string.type',
        AbstractField::DATETIME_T => 'date-time.type',
        AbstractField::TEXT_T =>     'text.type',
        AbstractField::MARKDOWN_T => 'markdown.type',
        AbstractField::BOOLEAN_T =>  'boolean.type',
        AbstractField::INTEGER_T =>  'integer.type',
        AbstractField::DECIMAL_T =>  'decimal.type',
        AbstractField::EMAIL_T =>    'email.type',
        AbstractField::ENUM_T =>     'single-choice.type',
        AbstractField::MULTIPLE_T => 'multiple-choice.type',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm", inversedBy="fields")
     * @ORM\JoinColumn(name="custom_form_id", onDelete="CASCADE")
     */
    private $customForm;

    /**
     * @return RZ\Roadiz\Core\Entities\CustomForm
     */
    public function getCustomForm()
    {
        return $this->customForm;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return $this
     */
    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormFieldAttribute", mappedBy="customFormField")
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
     * @ORM\Column(name="field_required", type="boolean")
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
     * @return  RZ\Roadiz\Core\Handlers\CustomFormFieldHandler
     */
    public function getHandler()
    {
        return new CustomFormFieldHandler($this);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        /*
         * Get the last index after last node in parent
         */
        $this->setPosition($this->getHandler()->cleanPositions());
    }

    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLabel().PHP_EOL;
    }
}
