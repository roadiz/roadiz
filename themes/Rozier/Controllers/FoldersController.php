<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file FoldersController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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

        return new Response(
            $this->getTwig()->render('folders/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an creation form for requested folder.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $folder = $this->addFolder($form); // only pass form for file handling

                $msg = $this->getTranslator()->trans('folder.%name%.created', array('%name%'=>$folder->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->warning($e->getMessage());
            } catch (\RuntimeException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->warning($e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('foldersHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('folders/add.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
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
                    $msg = $this->getTranslator()->trans('folder.%name%.deleted', array('%name%'=>$folder->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityRequiredException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('foldersHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['folder'] = $folder;

            return new Response(
                $this->getTwig()->render('folders/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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
                    $msg = $this->getTranslator()->trans('folder.%name%.updated', array('%name%'=>$folder->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('foldersHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['folder'] = $folder;
            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('folders/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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

            if ("" != $folder->getEOTFilename()) {
                $zip->addFile($folder->getEOTAbsolutePath(), $folder->getEOTFilename());
            }
            if ("" != $folder->getSVGFilename()) {
                $zip->addFile($folder->getSVGAbsolutePath(), $folder->getSVGFilename());
            }
            if ("" != $folder->getWOFFFilename()) {
                $zip->addFile($folder->getWOFFAbsolutePath(), $folder->getWOFFFilename());
            }
            if ("" != $folder->getOTFFilename()) {
                $zip->addFile($folder->getOTFAbsolutePath(), $folder->getOTFFilename());
            }
            // Close and send to users
            $zip->close();

            $filename = StringHandler::slugify($folder->getName().' '.$folder->getReadableVariant()).'.zip';

            $response = new Response(
                file_get_contents($file),
                Response::HTTP_OK,
                array(
                    'content-type' => 'application/zip',
                    'content-length' => filesize($file),
                    'content-disposition' => 'attachment; filename='.$filename
                )
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
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('folder.name'),
            ));

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
            ->add('folder_id', 'hidden', array(
                'data'=>$folder->getId()
            ));

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
        $defaults = array(
            'name'=>$folder->getName()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('folder_id', 'hidden', array(
                'data'=>$folder->getId()
            ))
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('folder.name'),
            ));

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
                    ->findOneBy(array('name' => $data['name']));

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
                    ->findOneBy(array('name' => $data['name']));
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
