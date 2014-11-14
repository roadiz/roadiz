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
 * @file SettingGroup.php
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
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\SettingGroupRepository")
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
     * @Column(type="boolean", name="in_menu", nullable=false)
     */
    protected $inMenu = false;

    /**
     * @return boolean
     */
    public function isInMenu()
    {
        return $this->inMenu;
    }
    /**
     * @param boolean $newinMenu
     */
    public function setInMenu($newinMenu)
    {
        $this->inMenu = $newinMenu;

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
