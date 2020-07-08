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
use RZ\Roadiz\OpenId\Authentication\Provider\SettingsRoleStrategy;
use RZ\Roadiz\OpenId\Authentication\Validator\DebugValidator;
use RZ\Roadiz\OpenId\Authentication\Validator\ExpirationValidator;
use RZ\Roadiz\OpenId\Authentication\Validator\HostedDomainValidator;
use RZ\Roadiz\OpenId\Authentication\Validator\IssuerValidator;
use RZ\Roadiz\OpenId\Authentication\Validator\SignatureValidator;
use RZ\Roadiz\OpenId\Authentication\Validator\UserInfoValidator;

class OpenIdServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[OAuth2LinkGenerator::class] = function (Container $c) {
            return new OAuth2LinkGenerator(
                $c[Discovery::class],
                $c['csrfTokenManager'],
                $c['settingsBag']
            );
        };

        $container['jwtRoleStrategies'] = function (Container $c) {
            return [
                new SettingsRoleStrategy($c['settingsBag'])
            ];
        };

        $container[JwtRoleStrategy::class] = function (Container $c) {
            return new ChainJwtRoleStrategy($c['jwtRoleStrategies']);
        };

        $container['oauth2AuthenticationProvider.validators'] = function (Container $c) {
            return [
                new ExpirationValidator(),
                new IssuerValidator($c[Discovery::class]),
                new SignatureValidator($c[Discovery::class]),
                new UserInfoValidator($c[Discovery::class]),
                new HostedDomainValidator($c['settingsBag']),
            ];
        };

        $container[OAuth2AuthenticationProvider::class] = function (Container $c) {
            return new OAuth2AuthenticationProvider(
                $c[Discovery::class],
                $c[JwtRoleStrategy::class],
                Kernel::SECURITY_DOMAIN,
                $c['oauth2AuthenticationProvider.validators'],
                [
                    Role::ROLE_DEFAULT
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
