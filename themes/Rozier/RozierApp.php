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
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Bags\SettingsBag;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;

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

        // Node tree
        $nodeTreeWidget = new NodeTreeWidget(Kernel::getInstance()->getRequest(), $this);
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' back-office';
        $this->assignation['nodeTree'] = new NodeTreeWidget(Kernel::getInstance()->getRequest(), $this);
        $this->assignation['tagTree'] = new TagTreeWidget(Kernel::getInstance()->getRequest(), $this);

        return $this;
    }

    /**
     * @return Symfony\Component\Form\Forms
     */
    protected function getFormFactory()
    {
        if (null === $this->formFactory) {

            $validator = Validation::createValidator();

            $this->formFactory = Forms::createFormFactoryBuilder()
                ->addExtension(new CsrfExtension($this->getKernel()->getCsrfProvider()))
                ->addExtension(new ValidatorExtension($validator))
                ->getFormFactory();
        }

        return $this->formFactory;
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
}