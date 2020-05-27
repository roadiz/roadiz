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
 * @file BackendController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Console\Tools\Requirements;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\Translator;
use Themes\Rozier\Widgets\FolderTreeWidget;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * Rozier main theme application
 */
class RozierApp extends BackendController
{
    protected static $themeName = 'Rozier Backstage theme';
    protected static $themeAuthor = 'Ambroise Maupate, Julien Blanchet';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'Rozier';

    protected $formFactory = null;
    protected $themeContainer = null;

    const DEFAULT_ITEM_PER_PAGE = 50;

    /**
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();
        /*
         * Use kernel DI container to delay API requuests
         */
        $this->themeContainer = $this->getContainer();
        $this->assignation['themeServices'] = $this->themeContainer;

        /*
         * Switch this to true to use uncompressed JS and CSS files
         */
        $this->assignation['head']['backDevMode'] = false;
        //Settings
        $this->assignation['head']['siteTitle'] = $this->get('settingsBag')->get('site_name') . ' backstage';
        $this->assignation['head']['mapsStyle'] = $this->get('settingsBag')->get('maps_style');
        $this->assignation['head']['mapsLocation'] = $this->get('settingsBag')->get('maps_default_location') ? $this->get('settingsBag')->get('maps_default_location') : null;
        $this->assignation['head']['mainColor'] = $this->get('settingsBag')->get('main_color');
        $this->assignation['head']['googleClientId'] = $this->get('settingsBag')->get('google_client_id', "");
        $this->assignation['head']['themeName'] = static::$themeName;
        $this->assignation['head']['ajaxToken'] = $this->get('csrfTokenManager')->getToken(static::AJAX_TOKEN_INTENTION);

        $this->themeContainer['nodeTree'] = function () {
            if (null !== $this->getUser() && $this->getUser() instanceof User) {
                $parent = $this->getUser()->getChroot();
            } else {
                $parent = null;
            }
            return new NodeTreeWidget($this->getRequest(), $this, $parent);
        };
        $this->themeContainer['tagTree'] = function () {
            return new TagTreeWidget($this->getRequest(), $this);
        };
        $this->themeContainer['folderTree'] = function () {
            return new FolderTreeWidget($this->getRequest(), $this);
        };
        $this->themeContainer['maxFilesize'] = function () {
            $requirements = new Requirements($this->get('kernel'));
            $post_max_size = $requirements->parseSuffixedAmount(ini_get('post_max_size') ?: '');
            $upload_max_filesize = $requirements->parseSuffixedAmount(ini_get('upload_max_filesize') ?: '');
            $maxFileSize = min($post_max_size, $upload_max_filesize);
            return $maxFileSize;
        };

        $this->themeContainer['settingGroups'] = function () {
            return $this->get('em')->getRepository(SettingGroup::class)
                ->findBy(
                    ['inMenu' => true],
                    ['name' => 'ASC']
                );
        };

        $this->themeContainer['adminImage'] = function () {
            /*
             * Get admin image
             */
            return $this->get('settingsBag')->getDocument('admin_image');
        };

        $this->assignation['nodeStatuses'] = [
            Node::getStatusLabel(Node::DRAFT) => Node::DRAFT,
            Node::getStatusLabel(Node::PENDING) => Node::PENDING,
            Node::getStatusLabel(Node::PUBLISHED) => Node::PUBLISHED,
            Node::getStatusLabel(Node::ARCHIVED) => Node::ARCHIVED,
            Node::getStatusLabel(Node::DELETED) => Node::DELETED,
        ];

        return $this;
    }

    /**
     * @param Request $request
     *
     * @return Response $response
     * @throws \Twig_Error_Runtime
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response $response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function cssAction(Request $request)
    {
        $this->assignation['mainColor'] = $this->get('settingsBag')->get('main_color');
        $this->assignation['nodeTypes'] = $this->get('em')->getRepository(NodeType::class)->findBy([]);
        $this->assignation['tags'] = $this->get('em')->getRepository(Tag::class)->findBy([
                'color' => ['!=', '#000000'],
            ]);

        return new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/css']
        );
    }

    /**
     * Append objects to global container.
     *
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $container->extend('translator', function (Translator $translator, $c) {
            $settingPath = __DIR__ . '/Resources/translations/settings.' . $c['translator.locale'] .  '.xlf';
            if (file_exists($settingPath)) {
                $translator->addResource(
                    'xlf',
                    $settingPath,
                    $c['translator.locale']
                );
            }
            $helpPath = __DIR__ . '/Resources/translations/helps.' . $c['translator.locale'] .  '.xlf';
            if (file_exists($helpPath)) {
                $translator->addResource(
                    'xlf',
                    $helpPath,
                    $c['translator.locale']
                );
            }
            return $translator;
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
                'roles' => ['ROLE_ACCESS_NODETYPES', 'ROLE_ACCESS_TRANSLATIONS', 'ROLE_ACCESS_THEMES', 'ROLE_ACCESS_FONTS'],
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
                    'manage.themes' => [
                        'name' => 'manage.themes',
                        'path' => $urlGenerator->generate('themesHomePage'),
                        'icon' => 'uk-icon-rz-themes',
                        'roles' => ['ROLE_ACCESS_THEMES'],
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
                    'ROLE_ACCESS_NEWSLETTERS',
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
                    /*
                     * Newsletter is not maintained anymore
                     * because never used…
                     */
                    /*'manage.newsletters' => [
                        'name' => 'manage.newsletters',
                        'path' => $urlGenerator->generate('newslettersIndexPage'),
                        'icon' => 'uk-icon-rz-newsletters',
                        'roles' => ['ROLE_ACCESS_NEWSLETTERS'],
                    ],*/
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
    }
}
