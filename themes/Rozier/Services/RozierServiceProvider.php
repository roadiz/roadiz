<?php
declare(strict_types=1);

namespace Themes\Rozier\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Node\NodeMover;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\FirewallMap;
use Themes\Rozier\Events\NodeDuplicationSubscriber;
use Themes\Rozier\Events\NodeRedirectionSubscriber;
use Themes\Rozier\Events\NodesSourcesUniversalSubscriber;
use Themes\Rozier\Events\NodesSourcesUrlSubscriber;
use Themes\Rozier\Events\TranslationSubscriber;
use Themes\Rozier\Forms\FolderCollectionType;
use Themes\Rozier\Forms\LoginType;
use Themes\Rozier\Forms\Node\AddNodeType;
use Themes\Rozier\Forms\Node\TranslateNodeType;
use Themes\Rozier\Forms\NodeTagsType;
use Themes\Rozier\Forms\NodeTreeType;
use Themes\Rozier\Forms\NodeType;
use Themes\Rozier\Forms\TranstypeType;
use Themes\Rozier\Serialization\DocumentThumbnailSerializeSubscriber;
use Themes\Rozier\Widgets\TreeWidgetFactory;
use Twig\Loader\FilesystemLoader;

final class RozierServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        /*
         * Overrideable form types
         */
        $container['rozier.form_type.add_node'] = AddNodeType::class;
        $container['rozier.form_type.node'] = NodeType::class;

        $container[TreeWidgetFactory::class] = function (Container $c) {
            return new TreeWidgetFactory($c['request_stack'], $c[ManagerRegistry::class]);
        };

        $container[NodeTreeType::class] = function (Container $c) {
            return new NodeTreeType(
                $c['securityAuthorizationChecker'],
                $c['request_stack'],
                $c[ManagerRegistry::class],
                $c[TreeWidgetFactory::class]
            );
        };

        $container[LoginType::class] = function (Container $c) {
            return new LoginType(
                $c['router'],
                $c['request_stack'],
            );
        };

        $container[AddNodeType::class] = function (Container $c) {
            return new AddNodeType($c[ManagerRegistry::class]);
        };

        $container[TranslateNodeType::class] = function (Container $c) {
            return new TranslateNodeType($c[ManagerRegistry::class]);
        };

        $container[FolderCollectionType::class] = function (Container $c) {
            return new FolderCollectionType($c[ManagerRegistry::class]);
        };

        $container[NodeTagsType::class] = function (Container $c) {
            return new NodeTagsType($c[ManagerRegistry::class]);
        };

        $container[TranstypeType::class] = function (Container $c) {
            return new TranstypeType($c[ManagerRegistry::class]);
        };

        $container->extend('serializer.subscribers', function (array $subscribers, $c) {
            $subscribers[] = new DocumentThumbnailSerializeSubscriber($c['document.url_generator']);
            return $subscribers;
        });

        $container->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            if (!$kernel->isInstallMode()) {
                /*
             * Add custom event subscriber to empty NS Url cache
             */
                $dispatcher->addSubscriber(
                    new NodesSourcesUrlSubscriber($c['nodesSourcesUrlCacheProvider'])
                );
                /*
                 * Add custom event subscriber to Translation result cache
                 */
                $dispatcher->addSubscriber(
                    new TranslationSubscriber($c['em']->getConfiguration()->getResultCacheImpl())
                );
                /*
                 * Add custom event subscriber to manage universal node-type fields
                 */
                $dispatcher->addSubscriber(
                    new NodesSourcesUniversalSubscriber($c[ManagerRegistry::class], $c['utils.universalDataDuplicator'])
                );
            }
            /*
             * Add custom event subscriber to manage node duplication
             */
            $dispatcher->addSubscriber(
                new NodeDuplicationSubscriber(
                    $c[ManagerRegistry::class],
                    $c['factory.handler']
                )
            );

            /*
             * Add event to create redirection after renaming a node.
             */
            $dispatcher->addSubscriber(
                new NodeRedirectionSubscriber($c[NodeMover::class], $kernel)
            );

            return $dispatcher;
        });

        $container->extend('backoffice.entries', function (array $entries, $c) {
            /** @var UrlGenerator $urlGenerator */
            $urlGenerator = $c['urlGenerator'];
            $entries['dashboard'] = [
                'name' => 'dashboard',
                'path' => $urlGenerator->generate('adminHomePage'),
                'icon' => 'uk-icon-rz-dashboard',
                'roles' => null,
                'subentries' => null,
            ];
            $entries['nodes'] = [
                'name' => 'nodes',
                'path' => null,
                'icon' => 'uk-icon-rz-global-nodes',
                'roles' => ['ROLE_ACCESS_NODES'],
                'subentries' => [
                    'all.nodes' => [
                        'name' => 'all.nodes',
                        'path' => $urlGenerator->generate('nodesHomePage'),
                        'icon' => 'uk-icon-rz-all-nodes',
                        'roles' => null,
                    ],
                    'draft.nodes' => [
                        'name' => 'draft.nodes',
                        'path' => $urlGenerator->generate('nodesHomeDraftPage'),
                        'icon' => 'uk-icon-rz-draft-nodes',
                        'roles' => null,
                    ],
                    'pending.nodes' => [
                        'name' => 'pending.nodes',
                        'path' => $urlGenerator->generate('nodesHomePendingPage'),
                        'icon' => 'uk-icon-rz-pending-nodes',
                        'roles' => null,
                    ],
                    'archived.nodes' => [
                        'name' => 'archived.nodes',
                        'path' => $urlGenerator->generate('nodesHomeArchivedPage'),
                        'icon' => 'uk-icon-rz-archives-nodes',
                        'roles' => null,
                    ],
                    'deleted.nodes' => [
                        'name' => 'deleted.nodes',
                        'path' => $urlGenerator->generate('nodesHomeDeletedPage'),
                        'icon' => 'uk-icon-rz-deleted-nodes',
                        'roles' => null,
                    ],
                    'search.nodes' => [
                        'name' => 'search.nodes',
                        'path' => $urlGenerator->generate('searchNodePage'),
                        'icon' => 'uk-icon-search',
                        'roles' => null,
                    ],
                ],
            ];
            $entries['manage.documents'] = [
                'name' => 'manage.documents',
                'path' => $urlGenerator->generate('documentsHomePage'),
                'icon' => 'uk-icon-rz-documents',
                'roles' => ['ROLE_ACCESS_DOCUMENTS'],
                'subentries' => null,
            ];
            $entries['manage.tags'] = [
                'name' => 'manage.tags',
                'path' => $urlGenerator->generate('tagsHomePage'),
                'icon' => 'uk-icon-rz-tags',
                'roles' => ['ROLE_ACCESS_TAGS'],
                'subentries' => null,
            ];
            $entries['construction'] = [
                'name' => 'construction',
                'path' => null,
                'icon' => 'uk-icon-rz-construction',
                'roles' => [
                    'ROLE_ACCESS_NODETYPES',
                    'ROLE_ACCESS_ATTRIBUTES',
                    'ROLE_ACCESS_TRANSLATIONS',
                    'ROLE_ACCESS_THEMES',
                    'ROLE_ACCESS_FONTS',
                    'ROLE_ACCESS_REDIRECTIONS',
                ],
                'subentries' => [
                    'manage.nodeTypes' => [
                        'name' => 'manage.nodeTypes',
                        'path' => $urlGenerator->generate('nodeTypesHomePage'),
                        'icon' => 'uk-icon-rz-manage-nodes',
                        'roles' => ['ROLE_ACCESS_NODETYPES'],
                    ],
                    'manage.attributes' => [
                        'name' => 'manage.attributes',
                        'path' => $urlGenerator->generate('attributesHomePage'),
                        'icon' => 'uk-icon-server',
                        'roles' => ['ROLE_ACCESS_ATTRIBUTES'],
                    ],
                    'manage.translations' => [
                        'name' => 'manage.translations',
                        'path' => $urlGenerator->generate('translationsHomePage'),
                        'icon' => 'uk-icon-rz-translate',
                        'roles' => ['ROLE_ACCESS_TRANSLATIONS'],
                    ],
                    'manage.fonts' => [
                        'name' => 'manage.fonts',
                        'path' => $urlGenerator->generate('fontsHomePage'),
                        'icon' => 'uk-icon-rz-fontes',
                        'roles' => ['ROLE_ACCESS_FONTS'],
                    ],
                    'manage.redirections' => [
                        'name' => 'manage.redirections',
                        'path' => $urlGenerator->generate('redirectionsHomePage'),
                        'icon' => 'uk-icon-compass',
                        'roles' => ['ROLE_ACCESS_REDIRECTIONS'],
                    ],
                ],
            ];

            $entries['user.system'] = [
                'name' => 'user.system',
                'path' => null,
                'icon' => 'uk-icon-rz-users',
                'roles' => ['ROLE_ACCESS_USERS', 'ROLE_ACCESS_ROLES', 'ROLE_ACCESS_GROUPS'],
                'subentries' => [
                    'manage.users' => [
                        'name' => 'manage.users',
                        'path' => $urlGenerator->generate('usersHomePage'),
                        'icon' => 'uk-icon-rz-user',
                        'roles' => ['ROLE_ACCESS_USERS'],
                    ],
                    'manage.roles' => [
                        'name' => 'manage.roles',
                        'path' => $urlGenerator->generate('rolesHomePage'),
                        'icon' => 'uk-icon-rz-roles',
                        'roles' => ['ROLE_ACCESS_ROLES'],
                    ],
                    'manage.groups' => [
                        'name' => 'manage.groups',
                        'path' => $urlGenerator->generate('groupsHomePage'),
                        'icon' => 'uk-icon-rz-groups',
                        'roles' => ['ROLE_ACCESS_GROUPS'],
                    ],
                ],
            ];

            $entries['interactions'] = [
                'name' => 'interactions',
                'path' => null,
                'icon' => 'uk-icon-rz-interactions',
                'roles' => [
                    'ROLE_ACCESS_CUSTOMFORMS',
                    'ROLE_ACCESS_MANAGE_SUBSCRIBERS',
                    'ROLE_ACCESS_COMMENTS',
                ],
                'subentries' => [
                    'manage.customForms' => [
                        'name' => 'manage.customForms',
                        'path' => $urlGenerator->generate('customFormsHomePage'),
                        'icon' => 'uk-icon-rz-surveys',
                        'roles' => ['ROLE_ACCESS_CUSTOMFORMS'],
                    ],
                ],
            ];

            $entries['settings'] = [
                'name' => 'settings',
                'path' => null,
                'icon' => 'uk-icon-rz-settings',
                'roles' => ['ROLE_ACCESS_SETTINGS'],
                'subentries' => [
                    'all.settings' => [
                        'name' => 'all.settings',
                        'path' => $urlGenerator->generate('settingsHomePage'),
                        'icon' => 'uk-icon-rz-settings-general',
                        'roles' => null,
                    ],
                    /*
                     * This entry is dynamic
                     */
                    'setting.groups.dynamic' => [
                        'name' => 'setting.groups.dynamic',
                        'path' => 'settingGroupsSettingsPage',
                        'icon' => 'uk-icon-rz-settings-group',
                        'roles' => null,
                    ],
                    'setting.groups' => [
                        'name' => 'setting.groups',
                        'path' => $urlGenerator->generate('settingGroupsHomePage'),
                        'icon' => 'uk-icon-rz-settings-groups',
                        'roles' => null,
                    ],
                ],
            ];

            return $entries;
        });

        $container->extend('twig.loaderFileSystem', function (FilesystemLoader $loader) {
            $loader->prependPath(dirname(__DIR__) . '/Resources/views', 'Rozier');
            $loader->prependPath(dirname(__DIR__) . '/Resources/views');
            return $loader;
        });

        $container->extend('assetPackages', function (Packages $packages, Container $c) {
            $packages->addPackage('Rozier', new PathPackage(
                'themes/Rozier/static',
                $c['versionStrategy'],
                new RequestStackContext($c['requestStack'])
            ));
            return $packages;
        });

        /*
         * Force login pages (connection, logout and reset) to be public
         * before rz-admin base pattern to be restricted
         */
        $container->extend('accessMap', function (AccessMap $accessMap, Container $c) {
            $accessMap->add(
                new RequestMatcher('^/rz-admin/login'),
                ['IS_AUTHENTICATED_ANONYMOUSLY']
            );
            $accessMap->add(
                new RequestMatcher('^/rz-admin/logout'),
                ['IS_AUTHENTICATED_ANONYMOUSLY']
            );
            return $accessMap;
        });

        $container->extend('firewallMap', function (FirewallMap $firewallMap, Container $c) {
            /*
            * Add default backend firewall entry.
            */
            $firewallBasePattern = '^/rz-admin';
            $firewallBasePath = '/rz-admin';
            $firewallLogin = $firewallBasePath . '/login';
            $firewallLogout = $firewallBasePath . '/logout';
            $firewallLoginCheck = $firewallBasePath . '/login_check';
            $firewallBaseRole = Role::ROLE_BACKEND_USER;

            $firewallEntry = new FirewallEntry(
                $c,
                $firewallBasePattern,
                $firewallBasePath,
                $firewallLogin,
                $firewallLogout,
                $firewallLoginCheck,
                $firewallBaseRole
            );
            $firewallEntry->withSwitchUserListener()
                ->withAnonymousAuthenticationListener()
                ->withOAuth2AuthenticationListener()
                ->withReferer();

            $firewallMap->add(
                $firewallEntry->getRequestMatcher(),
                $firewallEntry->getListeners(),
                $firewallEntry->getExceptionListener(true)
            );

            return $firewallMap;
        });
    }
}
