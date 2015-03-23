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
 * Description
 *
 * @file FoldersController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * Folders controller
 */
class FoldersController extends RozierApp
{

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Folder'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['folders'] = $listManager->getEntities();

        return $this->render('folders/list.html.twig', $this->assignation);
    }

    /**
     * Return an creation form for requested folder.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $parentFolderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {
            try {
                $folder = $this->addFolder($form); // only pass form for file handling

                $msg = $this->getTranslator()->trans(
                    'folder.%name%.created',
                    ['%name%' => $folder->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('foldersHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('folders/add.html.twig', $this->assignation);
    }

    /**
     * Return a deletion form for requested folder.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $folderId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $folderId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $folder = $this->getService('em')
                       ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

        if (null !== $folder) {
            $form = $this->buildDeleteForm($folder);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['folderId'] == $folder->getId()) {
                try {
                    $this->deleteFolder($form->getData(), $folder);
                    $msg = $this->getTranslator()->trans(
                        'folder.%name%.deleted',
                        ['%name%' => $folder->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityRequiredException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('foldersHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['folder'] = $folder;

            return $this->render('folders/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested folder.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $folderId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $folderId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $folder = $this->getService('em')
                       ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

        if ($folder !== null) {
            $form = $this->buildEditForm($folder);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['folderId'] == $folder->getId()) {
                try {
                    $this->editFolder($form, $folder); // only pass form for file handling
                    $msg = $this->getTranslator()->trans(
                        'folder.%name%.updated',
                        ['%name%' => $folder->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('foldersHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['folder'] = $folder;
            $this->assignation['form'] = $form->createView();

            return $this->render('folders/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }
    /**
     * Return a ZipArchive of requested folder.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $folderId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function downloadAction(Request $request, $folderId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $folder = $this->getService('em')
                       ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

        if ($folder !== null) {
            // Prepare File
            $file = tempnam("tmp", "zip");
            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::OVERWRITE);

            $documents = $this->getService('em')
                       ->getRepository('RZ\Roadiz\Core\Entities\Document')
                       ->findBy([
                            'folders' => [$folder]
                        ]);

            foreach ($documents as $document) {
                $zip->addFile($document->getAbsolutePath(), $document->getFilename());
            }

            // Close and send to users
            $zip->close();

            $filename = StringHandler::slugify($folder->getName()) .'.zip';

            $response = new Response(
                file_get_contents($file),
                Response::HTTP_OK,
                [
                    'content-type' => 'application/zip',
                    'content-length' => filesize($file),
                    'content-disposition' => 'attachment; filename=' . $filename,
                ]
            );
            unlink($file);

            return $response;
        } else {
            return $this->throw404();
        }
    }

    /**
     * Build add folder form with name constraint.
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm()
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('name', 'text', [
                            'label' => $this->getTranslator()->trans('folder.name'),
                        ]);

        return $builder->getForm();
    }

    /**
     * Build delete folder form with name constraint.
     * @param RZ\Roadiz\Core\Entities\Folder $folder
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Folder $folder)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('folder_id', 'hidden', [
                            'data' => $folder->getId(),
                        ]);

        return $builder->getForm();
    }

    /**
     * Build edit folder form with name constraint.
     * @param RZ\Roadiz\Core\Entities\Folder $folder
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Folder $folder)
    {
        $defaults = [
            'name' => $folder->getName(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add('folder_id', 'hidden', [
                            'data' => $folder->getId(),
                        ])
                        ->add('name', 'text', [
                            'label' => $this->getTranslator()->trans('folder.name'),
                        ]);

        return $builder->getForm();
    }

    /**
     * @param \Symfony\Component\Form\Form $rawData
     *
     * @return RZ\Roadiz\Core\Entities\Folder
     */
    protected function addFolder(\Symfony\Component\Form\Form $rawData)
    {

        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                             ->findOneBy(['name' => $data['name']]);

            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("folder.already_exists"), 1);
            }

            $folder = new Folder();
            $folder->setName($data['name']);

            $this->getService('em')->persist($folder);
            $this->getService('em')->flush();

            return $folder;
        } else {
            throw new \RuntimeException("Folder name is not defined", 1);
        }

        return null;
    }

    /**
     * @param \Symfony\Component\Form\Form  $rawData
     * @param RZ\Roadiz\Core\Entities\Folder $folder
     *
     * @return RZ\Roadiz\Core\Entities\Folder
     */
    protected function editFolder(\Symfony\Component\Form\Form $rawData, Folder $folder)
    {
        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                             ->findOneBy(['name' => $data['name']]);
            if ($existing !== null &&
                $existing->getId() != $folder->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("folder.name.already_exists"), 1);
            }

            $folder->setName($data['name']);

            $this->getService('em')->flush();

            return $folder;
        } else {
            throw new \RuntimeException("Folder name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Folder $folder
     *
     * @return void
     */
    protected function deleteFolder(array $data, Folder $folder)
    {
        $this->getService('em')->remove($folder);
        $this->getService('em')->flush();
    }
}
