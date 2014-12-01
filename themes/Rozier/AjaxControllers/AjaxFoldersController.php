<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxFoldersController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxFoldersController extends AbstractAjaxController
{
    /**
     * Handle AJAX edition requests for Folder
     * such as comming from tagtree widgets.
     *
     * @param Request $request
     * @param int     $folderId
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function editAction(Request $request, $folderId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $folder = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

        if ($folder !== null) {

            $responseArray = null;

            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $folder);
                    break;
            }

            if ($responseArray === null) {
                $responseArray = array(
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => ('Folder '.$folderId.' edited ')
                );
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }


        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => 'Folder '.$folderId.' does not exists'
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    public function searchAction(Request $request)
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

        $responseArray = array(
            'statusCode' => Response::HTTP_NOT_FOUND,
            'status'    => 'danger',
            'responseText' => 'No tags found'
        );

        if (!empty($request->get('search'))) {

            $responseArray = array();

            $pattern = strip_tags($request->get('search'));
            $folders = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                        ->searchBy(
                            $pattern,
                            array(),
                            array(),
                            10
                        );

            foreach ($folders as $folder) {
                $responseArray[] = $folder->getHandler()->getFullPath();
            }
        }

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    /**
     * @param array $parameters
     * @param Folder   $folder
     */
    protected function updatePosition($parameters, Folder $folder)
    {
        /*
         * First, we set the new parent
         */
        $parent = null;

        if (!empty($parameters['newParent']) &&
            $parameters['newParent'] > 0) {

            $parent = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $parameters['newParent']);

            if ($parent !== null) {
                $folder->setParent($parent);
            }
        } elseif ($parameters['newParent'] === null) {
            $folder->setParent(null);
        }

        /*
         * Then compute new position
         */
        if (!empty($parameters['nextFolderId']) &&
            $parameters['nextFolderId'] > 0) {
            $nextFolder = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $parameters['nextFolderId']);
            if ($nextFolder !== null) {
                $folder->setPosition($nextFolder->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevFolderId']) &&
            $parameters['prevFolderId'] > 0) {

            $prevFolder = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $parameters['prevFolderId']);
            if ($prevFolder !== null) {
                $folder->setPosition($prevFolder->getPosition() + 0.5);
            }
        }
        // Apply position update before cleaning
        $this->getService('em')->flush();

        if ($parent !== null) {
            $parent->getHandler()->cleanChildrenPositions();
        } else {
            FolderHandler::cleanRootFoldersPositions();
        }
    }
}
