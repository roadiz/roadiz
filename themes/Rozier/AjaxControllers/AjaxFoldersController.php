<?php
/*
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
 *
 * @file AjaxFoldersController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @return Response JSON response
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
                ['content-type' => 'application/javascript']
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
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => $this->getTranslator()->trans('folder.%name%.updated', [
                        '%name%' => $folder->getName(),
                    ])
                ];
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }


        $responseArray = [
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => $this->getTranslator()->trans('folder.does_not_exist')
        ];

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
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
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $responseArray = [
            'statusCode' => Response::HTTP_NOT_FOUND,
            'status'    => 'danger',
            'responseText' => $this->getTranslator()->trans('no.folder.found')
        ];

        if ($request->get('search') != "") {
            $responseArray = [];

            $pattern = strip_tags($request->get('search'));
            $folders = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                        ->searchBy(
                            $pattern,
                            [],
                            [],
                            10
                        );

            foreach ($folders as $folder) {
                $responseArray[] = $folder->getHandler()->getFullPath();
            }
        }

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
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
        } else {
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
