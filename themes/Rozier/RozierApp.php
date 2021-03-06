<?php
declare(strict_types=1);

namespace Themes\Rozier;

use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Console\Tools\Requirements;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\Widgets\FolderTreeWidget;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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

    public static $backendLanguages = [
        'Arabic' => 'ar',
        'English' => 'en',
        'Español' => 'es',
        'Français' => 'fr',
        'Indonesian' => 'id',
        'Italiano' => 'it',
        'Türkçe' => 'tr',
        'Русский язык' => 'ru',
        'српска ћирилица' => 'sr',
        '中文' => 'zh',
    ];

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
            return new NodeTreeWidget(
                $this->getRequest(),
                $this->get('em'),
                $this->get(NodeChrootResolver::class)->getChroot($this->getUser())
            );
        };
        $this->themeContainer['tagTree'] = function () {
            return new TagTreeWidget($this->getRequest(), $this->get('em'));
        };
        $this->themeContainer['folderTree'] = function () {
            return new FolderTreeWidget($this->getRequest(), $this->get('em'));
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
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response $response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function cssAction(Request $request)
    {
        $this->assignation['mainColor'] = $this->get('settingsBag')->get('main_color');
        $this->assignation['nodeTypes'] = $this->get('nodeTypesBag')->all();
        $this->assignation['tags'] = $this->get('em')->getRepository(Tag::class)->findBy([
            'color' => ['!=', '#000000'],
        ]);

        $response = new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            ['content-type' => 'text/css']
        );

        return $this->makeResponseCachable($request, $response, 30, true);
    }
}
