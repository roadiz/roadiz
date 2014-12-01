<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Setting.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Settings entity are a simple key-value configuration system.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\SettingRepository")
 * @ORM\Table(name="settings")
 */
class Setting extends AbstractEntity
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
     * @ORM\Column(type="boolean")
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
