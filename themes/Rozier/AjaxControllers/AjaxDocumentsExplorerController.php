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

use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use RZ\Roadiz\Core\ListManagers\EntityListManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxDocumentsExplorerController extends AbstractAjaxController
{
    public static $thumbnailArray = null;
    /**
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function indexAction(Request $request)
    {
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

        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $arrayFilter = array();
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Document',
            $arrayFilter
        );
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $documents = $listManager->getEntities();

        $documentsArray = array();
        foreach ($documents as $doc) {
            $documentsArray[] = array(
                'id' => $doc->getId(),
                'filename'=>$doc->getFilename(),
                'thumbnail' => $doc->getViewer()->getDocumentUrlByArray(AjaxDocumentsExplorerController::$thumbnailArray),
                'html' => $this->getTwig()->render('widgets/documentSmallThumbnail.html.twig', array('document'=>$doc)),
            );
        }

        $responseArray = array(
            'status' => 'confirm',
            'statusCode' => 200,
            'documents' => $documentsArray,
            'documentsCount' => count($documents),
            'filters' => $listManager->getAssignation()
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
AjaxDocumentsExplorerController::$thumbnailArray = array(
    "width"=>40,
    "crop"=>"1x1",
    "quality"=>50
);
