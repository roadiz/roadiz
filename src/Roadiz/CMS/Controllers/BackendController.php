<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Component\HttpFoundation\RequestMatcher;

/**
 * Special controller app file for backend themes.
 *
 * This AppController implementation will use a security scheme
 */
abstract class BackendController extends AppController
{
    protected static $backendTheme = true;

    /**
     * {@inheritdoc}
     */
    public static $priority = -10;

    /**
     * Append objects to global container.
     *
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $firewallBasePattern = '^/rz-admin';
        $firewallBasePath = '/rz-admin';
        $firewallLogin = $firewallBasePath . '/login';
        $firewallLogout = $firewallBasePath . '/logout';
        $firewallLoginCheck = $firewallBasePath . '/login_check';
        $firewallBaseRole = Role::ROLE_BACKEND_USER;

        /*
         * Force login pages (connection, logout and reset) to be public
         * before rz-admin base pattern to be restricted
         */
        $container['accessMap']->add(
            new RequestMatcher('^/rz-admin/login'),
            ['IS_AUTHENTICATED_ANONYMOUSLY']
        );
        $container['accessMap']->add(
            new RequestMatcher('^/rz-admin/logout'),
            ['IS_AUTHENTICATED_ANONYMOUSLY']
        );

        $firewallEntry = new FirewallEntry(
            $container,
            $firewallBasePattern,
            $firewallBasePath,
            $firewallLogin,
            $firewallLogout,
            $firewallLoginCheck,
            $firewallBaseRole
        );
        $firewallEntry->withSwitchUserListener()
            ->withAnonymousAuthenticationListener()
            ->withReferer();

        $container['firewallMap']->add(
            $firewallEntry->getRequestMatcher(),
            $firewallEntry->getListeners(),
            $firewallEntry->getExceptionListener(true)
        );
    }

    /**
     * @inheritDoc
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        return parent::createEntityListManager($entity, $criteria, $ordering)
            ->setDisplayingNotPublishedNodes(true);
    }
}
