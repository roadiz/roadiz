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
 * @file SettingGroup.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * Settings entity are a simple key-value configuration system.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\SettingGroupRepository")
 * @ORM\Table(name="settings_groups")
 *
 */
class SettingGroup extends AbstractEntity
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
        $this->name = $name;
        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="in_menu", nullable=false, options={"default" = false})
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
     * @ORM\OneToMany(targetEntity="Setting", mappedBy="settingGroup")
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
     * @param \RZ\Roadiz\Core\Entities\Setting $setting
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
