<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

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
     * @Serializer\Groups({"setting", "setting_group"})
     * @Serializer\Type("string")
     * @var string
     */
    private $name = '';

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
     * @return SettingGroup
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="in_menu", nullable=false, options={"default" = false})
     * @Serializer\Groups({"setting", "setting_group"})
     * @Serializer\Type("bool")
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
     * @return SettingGroup
     */
    public function setInMenu($newinMenu)
    {
        $this->inMenu = $newinMenu;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="Setting", mappedBy="settingGroup")
     * @var Collection<Setting>
     * @Serializer\Groups({"setting_group"})
     */
    private Collection $settings;

    public function __construct()
    {
        $this->settings = new ArrayCollection();
    }
    /**
     * @return Collection
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }
    /**
     * @param Setting $setting
     * @return SettingGroup
     */
    public function addSetting(Setting $setting)
    {
        if (!$this->getSettings()->contains($setting)) {
            $this->settings->add($setting);
        }
        return $this;
    }

    /**
     * @param Collection<Setting> $settings
     * @return SettingGroup
     */
    public function addSettings(Collection $settings)
    {
        foreach ($settings as $setting) {
            if (!$this->getSettings()->contains($setting)) {
                $this->settings->add($setting);
            }
        }
        return $this;
    }
}
