<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxDocumentsExplorerController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class AjaxDocumentsExplorerController extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function indexAction(Request $request)
    {
        if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_DOCUMENTS')
            || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
            return $this->throw404();

        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }

        $documents = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Document')
            ->findBy(array(), array('createdAt' => 'DESC'));

        $documentsArray = array();
        foreach ($documents as $doc) {
            $documentsArray[] = array(
                'id' => $doc->getId(),
                'filename'=>$doc->getFilename(),
                'thumbnail' => $doc->getViewer()->getDocumentUrlByArray(array("width"=>40, "crop"=>"1x1", "quality"=>50)),
                'html' => $this->getTwig()->render('widgets/documentSmallThumbnail.html.twig', array('document'=>$doc)),
            );
        }

        $responseArray = array(
            'status' => 'confirm',
            'statusCode' => 200,
            'documents' => $documentsArray,
            'documentsCount' => count($documents)
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
