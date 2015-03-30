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

use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;
use Themes\Rozier\Widgets\FolderTreeWidget;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;

/**
 * Rozier main theme application
 */
class RozierApp extends BackendController
{
    protected static $themeName =      'Rozier Backstage theme';
    protected static $themeAuthor =    'Ambroise Maupate, Julien Blanchet';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'Rozier';

    protected $formFactory = null;
    protected $themeContainer = null;

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        /*
         * Use kernel DI container to delay API requuests
         */
        $this->themeContainer = $this->getService();
        $this->assignation['themeServices'] = $this->themeContainer;

        /*
         * Switch this to true to use uncompressed JS and CSS files
         */
        $this->assignation['head']['backDevMode'] = false;
        //Settings
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' backstage';
        $this->assignation['head']['mapsStyle'] = SettingsBag::get('maps_style');
        $this->assignation['head']['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['head']['googleClientId'] = SettingsBag::get('google_client_id') ? SettingsBag::get('google_client_id') : "";

        $this->themeContainer['nodeTree'] = function ($c) {
            if (!empty($this->assignation['session']["user"])) {
                $parent = $this->assignation['session']["user"]->getChroot();
            } else {
                $parent = null;
            }
            return new NodeTreeWidget($this->getKernel()->getRequest(), $this, $parent);
        };
        $this->themeContainer['tagTree'] = function ($c) {
            return new TagTreeWidget($this->getKernel()->getRequest(), $this);
        };
        $this->themeContainer['folderTree'] = function ($c) {
            return new FolderTreeWidget($this->getKernel()->getRequest(), $this);
        };
        $this->themeContainer['maxFilesize'] = function ($c) {
            return min(intval(ini_get('post_max_size')), intval(ini_get('upload_max_filesize')));
        };

        $this->themeContainer['grunt'] = function ($c) {
            return include(dirname(__FILE__).'/static/public/config/assets.config.php');
        };

        $this->themeContainer['settingGroups'] = function ($c) {
            return $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                                          ->findBy(
                                              ['inMenu' => true],
                                              ['name'=>'ASC']
                                          );
        };

        $this->themeContainer['adminImage'] = function ($c) {
            /*
             * Get admin image
             */
            return SettingsBag::getDocument('admin_image');
        };


        $this->assignation['nodeStatuses'] = [
            'draft' => Node::DRAFT,
            'pending' => Node::PENDING,
            'published' => Node::PUBLISHED,
            'archived' => Node::ARCHIVED,
            'deleted' => Node::DELETED
        ];

        return $this;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig', $this->assignation);
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function cssAction(Request $request)
    {
        $this->assignation['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['nodeTypes'] = $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                                              ->findBy([]);

        return new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/css']
        );
    }

    /**
     * Append objects to global container.
     *
     * @param Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        BackendController::setupDependencyInjection($container);

        $container->extend('backoffice.entries', function (array $entries, $c) {

            $entries['dashboard'] = [
                'name' => 'dashboard',
                'path' => $c['urlGenerator']->generate('adminHomePage'),
                'icon' => 'uk-icon-rz-dashboard',
                'roles' => null,
                'subentries' => null
            ];
            $entries['nodes'] = [
                'name' => 'nodes',
                'path' => null,
                'icon' => 'uk-icon-rz-global-nodes',
                'roles' => ['ROLE_ACCESS_NODES'],
                'subentries' => [
                    'all.nodes' => [
                        'name' => 'all.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomePage'),
                        'icon' => 'uk-icon-rz-all-nodes',
                        'roles' => null
                    ],
                    'draft.nodes' => [
                        'name' => 'draft.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeDraftPage'),
                        'icon' => 'uk-icon-rz-draft-nodes',
                        'roles' => null
                    ],
                    'pending.nodes' => [
                        'name' => 'pending.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomePendingPage'),
                        'icon' => 'uk-icon-rz-pending-nodes',
                        'roles' => null
                    ],
                    'archived.nodes' => [
                        'name' => 'archived.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeArchivedPage'),
                        'icon' => 'uk-icon-rz-archives-nodes',
                        'roles' => null
                    ],
                    'deleted.nodes' => [
                        'name' => 'deleted.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeDeletedPage'),
                        'icon' => 'uk-icon-rz-deleted-nodes',
                        'roles' => null
                    ],
                    'search.nodes' => [
                        'name' => 'search.nodes',
                        'path' => $c['urlGenerator']->generate('searchNodePage'),
                        'icon' => 'uk-icon-search',
                        'roles' => null
                    ],
                ]
            ];
            $entries['manage.documents'] = [
                'name' => 'manage.documents',
                'path' => $c['urlGenerator']->generate('documentsHomePage'),
                'icon' => 'uk-icon-rz-documents',
                'roles' => ['ROLE_ACCESS_DOCUMENTS'],
                'subentries' => null
            ];
            $entries['manage.tags'] = [
                'name' => 'manage.tags',
                'path' => $c['urlGenerator']->generate('tagsHomePage'),
                'icon' => 'uk-icon-rz-tags',
                'roles' => ['ROLE_ACCESS_TAGS'],
                'subentries' => null
            ];
            $entries['construction'] = [
                'name' => 'construction',
                'path' => null,
                'icon' => 'uk-icon-rz-construction',
                'roles' => ['ROLE_ACCESS_NODETYPES', 'ROLE_ACCESS_TRANSLATIONS', 'ROLE_ACCESS_THEMES', 'ROLE_ACCESS_FONTS'],
                'subentries' => [
                    'manage.nodeTypes' => [
                        'name' => 'manage.nodeTypes',
                        'path' => $c['urlGenerator']->generate('nodeTypesHomePage'),
                        'icon' => 'uk-icon-rz-manage-nodes',
                        'roles' => ['ROLE_ACCESS_NODETYPES']
                    ],
                    'manage.translations' => [
                        'name' => 'manage.translations',
                        'path' => $c['urlGenerator']->generate('translationsHomePage'),
                        'icon' => 'uk-icon-rz-translate',
                        'roles' => ['ROLE_ACCESS_TRANSLATIONS']
                    ],
                    'manage.themes' => [
                        'name' => 'manage.themes',
                        'path' => $c['urlGenerator']->generate('themesHomePage'),
                        'icon' => 'uk-icon-rz-themes',
                        'roles' => ['ROLE_ACCESS_THEMES']
                    ],
                    'manage.fonts' => [
                        'name' => 'manage.fonts',
                        'path' => $c['urlGenerator']->generate('fontsHomePage'),
                        'icon' => 'uk-icon-rz-fontes',
                        'roles' => ['ROLE_ACCESS_FONTS']
                    ],
                ]
            ];

            $entries['user.system'] = [
                'name' => 'user.system',
                'path' => null,
                'icon' => 'uk-icon-rz-users',
                'roles' => ['ROLE_ACCESS_USERS', 'ROLE_ACCESS_ROLES', 'ROLE_ACCESS_GROUPS'],
                'subentries' => [
                    'manage.users' => [
                        'name' => 'manage.users',
                        'path' => $c['urlGenerator']->generate('usersHomePage'),
                        'icon' => 'uk-icon-rz-user',
                        'roles' => ['ROLE_ACCESS_USERS']
                    ],
                    'manage.roles' => [
                        'name' => 'manage.roles',
                        'path' => $c['urlGenerator']->generate('rolesHomePage'),
                        'icon' => 'uk-icon-rz-roles',
                        'roles' => ['ROLE_ACCESS_ROLES']
                    ],
                    'manage.groups' => [
                        'name' => 'manage.groups',
                        'path' => $c['urlGenerator']->generate('groupsHomePage'),
                        'icon' => 'uk-icon-rz-groups',
                        'roles' => ['ROLE_ACCESS_GROUPS']
                    ]
                ]
            ];

            $entries['interactions'] = [
                'name' => 'interactions',
                'path' => null,
                'icon' => 'uk-icon-rz-interactions',
                'roles' => [
                    'ROLE_ACCESS_CUSTOMFORMS',
                    'ROLE_ACCESS_NEWSLETTERS',
                    'ROLE_ACCESS_MANAGE_SUBSCRIBERS',
                    'ROLE_ACCESS_COMMENTS'
                ],
                'subentries' => [
                    'manage.customForms' => [
                        'name' => 'manage.customForms',
                        'path' => $c['urlGenerator']->generate('customFormsHomePage'),
                        'icon' => 'uk-icon-rz-surveys',
                        'roles' => ['ROLE_ACCESS_CUSTOMFORMS']
                    ],
                    'manage.newsletters' => [
                        'name' => 'manage.newsletters',
                        'path' => $c['urlGenerator']->generate('newslettersIndexPage'),
                        'icon' => 'uk-icon-rz-newsletters',
                        'roles' => ['ROLE_ACCESS_NEWSLETTERS']
                    ],
                    'manage.subscribers' => [
                        'name' => 'manage.subscribers',
                        'path' => null,
                        'icon' => 'uk-icon-rz-subscribers',
                        'roles' => ['ROLE_ACCESS_MANAGE_SUBSCRIBERS']
                    ],
                    'manage.comments' => [
                        'name' => 'manage.comments',
                        'path' => null,
                        'icon' => 'uk-icon-rz-comments',
                        'roles' => ['ROLE_ACCESS_COMMENTS']
                    ],
                ]
            ];

            $entries['settings'] = [
                'name' => 'settings',
                'path' => null,
                'icon' => 'uk-icon-rz-settings',
                'roles' => ['ROLE_ACCESS_SETTINGS'],
                'subentries' => [
                    'all.settings' => [
                        'name' => 'all.settings',
                        'path' => $c['urlGenerator']->generate('settingsHomePage'),
                        'icon' => 'uk-icon-rz-settings-general',
                        'roles' => null
                    ],
                    /*
                     * This entry is dynamic
                     */
                    'setting.groups.dynamic' => [
                        'name' => 'setting.groups.dynamic',
                        'path' => 'settingGroupsSettingsPage',
                        'icon' => 'uk-icon-rz-settings-group',
                        'roles' => null
                    ],
                    'setting.groups' => [
                        'name' => 'setting.groups',
                        'path' => $c['urlGenerator']->generate('settingGroupsHomePage'),
                        'icon' => 'uk-icon-rz-settings-groups',
                        'roles' => null
                    ]
                ]
            ];

            return $entries;
        });
    }
}
