<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\Setting;

final class SettingExplorerItem extends AbstractExplorerItem
{
    private Setting $setting;

    /**
     * @param Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->setting->getId();
    }

    /**
     * @inheritDoc
     */
    public function getAlternativeDisplayable()
    {
        if (null !== $this->setting->getSettingGroup()) {
            return $this->setting->getSettingGroup()->getName();
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getDisplayable()
    {
        return $this->setting->getName();
    }

    /**
     * @inheritDoc
     */
    public function getOriginal()
    {
        return $this->setting;
    }
}
