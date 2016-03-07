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
 * @file Setting.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;


use RZ\Roadiz\Utils\StringHandler;

/**
 * Settings entity are a simple key-value configuration system.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\SettingRepository")
 * @ORM\Table(name="settings")
 */
class Setting extends AbstractEntity
{

    /**
     * Associates custom form field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = [
        AbstractField::STRING_T => 'string.type',
        AbstractField::DATETIME_T => 'date-time.type',
        AbstractField::TEXT_T => 'text.type',
        AbstractField::BOOLEAN_T => 'boolean.type',
        AbstractField::INTEGER_T => 'integer.type',
        AbstractField::DECIMAL_T => 'decimal.type',
        AbstractField::EMAIL_T => 'email.type',
        AbstractField::DOCUMENTS_T => 'documents.type',
        AbstractField::COLOUR_T => 'colour.type',
        AbstractField::JSON_T => 'json.type',
    ];

    /**
     * Associates node-type field type to a Symfony Form type.
     *
     * @var array
     */
    public static $typeToForm = [
        AbstractField::STRING_T => 'text',
        AbstractField::DATETIME_T => 'datetime',
        AbstractField::TEXT_T => 'textarea',
        AbstractField::BOOLEAN_T => 'checkbox',
        AbstractField::INTEGER_T => 'integer',
        AbstractField::DECIMAL_T => 'number',
        AbstractField::EMAIL_T => 'email',
        AbstractField::DOCUMENTS_T => 'file',
        AbstractField::COLOUR_T => 'text',
        AbstractField::JSON_T => 'json',
    ];

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
        $this->name = trim(strtolower($name));
        $this->name = StringHandler::removeDiacritics($this->name);
        $this->name = preg_replace('#([^a-z])#', '_', $this->name);

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;
    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->getType() == NodeTypeField::BOOLEAN_T) {
            return (boolean) $this->value;
        }
        if ($this->getType() == NodeTypeField::DATETIME_T) {
            return new \DateTime($this->value);
        }
        if ($this->getType() == NodeTypeField::DOCUMENTS_T) {
            return (int) $this->value;
        }

        return $this->value;
    }
    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        if ($this->getType() == NodeTypeField::DATETIME_T) {
            $this->value = $value->format('Y-m-d H:i:s'); // $value is instance of \DateTime
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $visible = true;
    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }
    /**
     * @param boolean $visible
     *
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = (boolean) $visible;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="SettingGroup", inversedBy="settings")
     * @ORM\JoinColumn(name="setting_group_id", referencedColumnName="id")
     * @var SettingGroup
     */
    private $settingGroup;
    /**
     * @return SettingGroup
     */
    public function getSettingGroup()
    {
        return $this->settingGroup;
    }
    /**
     * @param SettingGroup $settingGroup
     *
     * @return $this
     */
    public function setSettingGroup($settingGroup)
    {
        $this->settingGroup = $settingGroup;

        return $this;
    }

    /**
     * Value types.
     * Use NodeTypeField types constants.
     *
     * @ORM\Column(type="integer")
     */
    private $type = NodeTypeField::STRING_T;
    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * @param integer $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (int) $type;

        return $this;
    }
}
