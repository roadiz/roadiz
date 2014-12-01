<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier;

use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;
use Themes\Rozier\Widgets\FolderTreeWidget;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Rozier main theme application
 */
class RozierApp extends BackendController
{
    protected static $themeName =      'Rozier administration theme';
    protected static $themeAuthor =    'Ambroise Maupate, Julien Blanchet';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'Rozier';

    protected $formFactory = null;

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        if (!$this->getKernel()->getRequest()->isXmlHttpRequest()) {
            $this->assignation['nodeTree'] = new NodeTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['tagTree'] = new TagTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['folderTree'] = new FolderTreeWidget($this->getKernel()->getRequest(), $this);
        }

        //Settings
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' back-office';
        $this->assignation['head']['mapsStyle'] = SettingsBag::get('maps_style');

        //[{"featureType":"water","elementType":"all","stylers":[{"color":"#636363"}]},{"featureType":"landscape.natural","elementType":"all","stylers":[{"color":"#ffffff"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#c7c7c7"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"color":"#a6a6a6"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"visibility":"simplified"},{"color":"#ffffff"},{"weight":3.04}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"visibility":"simplified"},{"color":"#e6e6e6"}]}]
        //
        $this->assignation['head']['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['head']['googleClientId'] = SettingsBag::get('google_client_id') ? SettingsBag::get('google_client_id') : "";

        $this->assignation['head']['grunt'] = include(dirname(__FILE__).'/static/public/config/assets.config.php');

        $this->assignation['settingGroups'] = $this->getService('em')
                                                   ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                                                   ->findBy(array('inMenu' => true), array('name'=>'ASC'));

        /*
         * Get admin image
         */
        $adminImage = $this->getService('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\DocumentTranslation')
                           ->findOneBy(array(
                                'name' => '_admin_image_'
                            ));
        if (null !== $adminImage) {
            $this->assignation['adminImage'] = $adminImage->getDocument();
        }

        $this->assignation['nodeStatuses'] = array(
            'draft' => Node::DRAFT,
            'pending' => Node::PENDING,
            'published' => Node::PUBLISHED,
            'archived' => Node::ARCHIVED,
            'deleted' => Node::DELETED
        );

        return $this;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        return new Response(
            $this->getTwig()->render('index.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function cssAction(Request $request)
    {

        $this->assignation['mainColor'] = SettingsBag::get('main_color');

        // var_dump(SettingsBag::get('main_color'));

        return new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/css')
        );
    }
}
