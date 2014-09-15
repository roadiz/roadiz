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
use RZ\Renzo\Core\Entities\Setting;
/**
 * Settings entity are a simple key-value configuration system.
 *
 * @Entity
 * @Table(name="settings_groups")
 *
 */
class SettingGroup extends AbstractEntity
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
        $this->name = $name;
        return $this;
    }


    /**
     * @OneToMany(targetEntity="Setting", mappedBy="settingGroup")
     * @var ArrayCollection
     *
     */
    private $settings;
    /**
     * @{inheritdoc}
     */
    public function __construct()
    {
        $this->settings = new ArrayCollection();
    }
    /**
     * @return ArrayCollection
     */
    public function getSettings()
    {
        return $this->settings;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return $this
     */
    public function addSetting($setting)
    {
        if (!$this->getSettings()->contains($setting)) {
            $this->settings->add($setting);
        }
        return $this;
    }

    /**
     * @param ArrayCollection $settings
     *
     * @return $this
     */
    public function addSettings($settings)
    {
        foreach ($settings as $setting) {
            if (!$this->getSettings()->contains($setting)) {
                $this->settings->add($setting);
            }
        }
    }
}