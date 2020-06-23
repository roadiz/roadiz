<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId;

use Doctrine\Common\Cache\CacheProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\OpenId\Authentication\Provider\ChainJwtRoleStrategy;
use RZ\Roadiz\OpenId\Authentication\Provider\JwtRoleStrategy;
use RZ\Roadiz\OpenId\Authentication\Provider\OAuth2AuthenticationProvider;
use RZ\Roadiz\OpenId\Authentication\Provider\OpenIdAccountProvider;

class OpenIdServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container['jwtRoleStrategies'] = function (Container $c) {
            return [

            ];
        };

        $container[JwtRoleStrategy::class] = function (Container $c) {
            return new ChainJwtRoleStrategy($c['jwtRoleStrategies']);
        };

        $container[OAuth2AuthenticationProvider::class] = function (Container $c) {
            return new OAuth2AuthenticationProvider(
                $c[JwtRoleStrategy::class],
                Kernel::SECURITY_DOMAIN,
                [
                    Role::ROLE_SUPERADMIN
                ]
            );
        };

        $container[OpenIdAccountProvider::class] = function () {
            return new OpenIdAccountProvider();
        };

        $container->extend('userProviders', function (array $providers, Container $c) {
            return array_merge($providers, [
                $c[OpenIdAccountProvider::class],
            ]);
        });

        $container->extend('authenticationProviderList', function (array $providers, Container $c) {
            return array_merge($providers, [
                $c[OAuth2AuthenticationProvider::class]
            ]);
        });

        $container[Discovery::class] = function (Container $c) {
            $discoveryUri = $c['settingsBag']->get('openid_discovery', '');
            if (!empty($discoveryUri)) {
                return new Discovery(
                    $discoveryUri,
                    $c[CacheProvider::class]
                );
            }
            return null;
        };
    }
}
