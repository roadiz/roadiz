<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file Setting.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Entities\NodeTypeField;
/**
 * Settings entity are a simple key-value configuration system.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\SettingRepository")
 * @Table(name="settings")
 */
class Setting extends AbstractEntity
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
        $this->name = trim(strtolower($name));
        $this->name = StringHandler::removeDiacritics($this->name);
        $this->name = preg_replace('#([^a-z])#', '_', $this->name);

        return $this;
    }

    /**
     * @Column(type="text", nullable=true)
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
     * @Column(type="boolean")
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
     * Value types.
     * Use NodeTypeField types constants.
     *
     * @Column(type="integer")
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