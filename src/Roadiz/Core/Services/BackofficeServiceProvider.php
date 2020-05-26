<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Register Embed documents services for dependency injection container.
 */
class BackofficeServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize backoffice admin entries.
     *
     * You can extend Roadiz backoffice menu adding entries in
     * `backoffice.entries` service. Each entry must follow this structure:
     *
     *     'name' => 'my.new.feature',
     *     'path' => $c['urlGenerator']->generate('myNewFeaturePage'),
     *     'icon' => 'uk-icon-new-feature',
     *     'roles' => array('ROLE_ACCESS_MYNEWFEATURE'),
     *     'subentries' => null
     *
     *
     * @param Container $container
     *
     * @return Container
     */
    public function register(Container $container)
    {
        $container['backoffice.entries'] = function () {
            return [

            ];
        };

        return $container;
    }
}
