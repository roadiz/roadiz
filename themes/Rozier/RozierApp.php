<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier;

use RZ\Renzo\CMS\Controllers\BackendController;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Bags\SettingsBag;

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
        // Node tree
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' back-office';
        $this->assignation['head']['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['head']['grunt'] = include(dirname(__FILE__).'/static/public/config/assets.config.php');

        $this->assignation['settingGroups'] = $this->getService('em')
                                                   ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
                                                   ->findBy(array('inMenu' => true), array('name'=>'ASC'));

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
