<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;

class SettingsRoleStrategy implements JwtRoleStrategy
{
    const SETTING_NAME = 'openid_default_roles';
    /**
     * @var Settings
     */
    protected $settingsBag;

    /**
     * SettingsRoleStrategy constructor.
     *
     * @param Settings $settingsBag
     */
    public function __construct(Settings $settingsBag)
    {
        $this->settingsBag = $settingsBag;
    }

    public function supports(JwtAccountToken $token): bool
    {
        return null !== $this->settingsBag && !empty($this->settingsBag->get(static::SETTING_NAME));
    }

    public function getRoles(JwtAccountToken $token): ?array
    {
        return array_map(function ($role) {
            return trim($role);
        }, explode(',', $this->settingsBag->get(static::SETTING_NAME)));
    }
}
