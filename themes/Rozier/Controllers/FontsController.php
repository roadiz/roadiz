<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file FontsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Font;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\EntityRequiredException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Fonts controller
 */
class FontsController extends RozierApp
{

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validedAccessForRole('ROLE_ACCESS_FONTS');

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\Font'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['fonts'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('fonts/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an creation form for requested font.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validedAccessForRole('ROLE_ACCESS_FONTS');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_FONTS')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $font = $this->addFont($form); // only pass form for file handling

                $msg = $this->getTranslator()->trans('font.created', array('%name%'=>$font->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

            } catch (EntityAlreadyExistsException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getLogger()->warning($e->getMessage());
            } catch (\RuntimeException $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getLogger()->warning($e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('fontsHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('fonts/add.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return a deletion form for requested font.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $fontId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $fontId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Font', (int) $fontId);

        if (null !== $font) {
            $form = $this->buildDeleteForm($font);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {

                try {
                    $this->deleteFont($form->getData(), $font);
                    $msg = $this->getTranslator()->trans('font.deleted', array('%name%'=>$font->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                } catch (EntityRequiredException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('fontsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('fonts/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested font.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $fontId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $fontId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Font', (int) $fontId);

        if ($font !== null) {

            $form = $this->buildEditForm($font);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {

                try {
                    $this->editFont($form, $font); // only pass form for file handling
                    $msg = $this->getTranslator()->trans('font.updated', array('%name%'=>$font->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                } catch (\RuntimeException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('fontsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('fonts/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }
    /**
     * Return a ZipArchive of requested font.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $fontId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function downloadAction(Request $request, $fontId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Font', (int) $fontId);

        if ($font !== null) {

            // Prepare File
            $file = tempnam("tmp", "zip");
            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::OVERWRITE);

            if ("" != $font->getEOTFilename()) {
                $zip->addFile($font->getEOTAbsolutePath(), $font->getEOTFilename());
            }
            if ("" != $font->getSVGFilename()) {
                $zip->addFile($font->getSVGAbsolutePath(), $font->getSVGFilename());
            }
            if ("" != $font->getWOFFFilename()) {
                $zip->addFile($font->getWOFFAbsolutePath(), $font->getWOFFFilename());
            }
            if ("" != $font->getOTFFilename()) {
                $zip->addFile($font->getOTFAbsolutePath(), $font->getOTFFilename());
            }
            // Close and send to users
            $zip->close();

            $filename = StringHandler::slugify($font->getName().' '.$font->getReadableVariant()).'.zip';

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
     * Build add font form with name constraint.
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildAddForm()
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('font.name'),
            ))
            ->add('variant', new \RZ\Renzo\CMS\Forms\FontVariantsType(), array(
                'label' => $this->getTranslator()->trans('font.variant')
            ))
            ->add('eotFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.eotFile'),
                'required' => false
            ))
            ->add('woffFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.woffFile'),
                'required' => false
            ))
            ->add('otfFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.otfFile'),
                'required' => false
            ))
            ->add('svgFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.svgFile'),
                'required' => false
            ));

        return $builder->getForm();
    }

    /**
     * Build delete font form with name constraint.
     * @param RZ\Renzo\Core\Entities\Font $font
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Font $font)
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('font_id', 'hidden', array(
                'data'=>$font->getId()
            ));

        return $builder->getForm();
    }

    /**
     * Build edit font form with name constraint.
     * @param RZ\Renzo\Core\Entities\Font $font
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Font $font)
    {
        $defaults = array(
            'name'=>$font->getName(),
            'variant'=>$font->getVariant()
        );
        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('font_id', 'hidden', array(
                'data'=>$font->getId()
            ))
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('font.name'),
            ))
            ->add(
                'variant',
                new \RZ\Renzo\CMS\Forms\FontVariantsType(),
                array(
                    'label' => $this->getTranslator()->trans('font.variant')
                )
            )
            ->add('eotFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.eotFile'),
                'required' => false
            ))
            ->add('woffFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.woffFile'),
                'required' => false
            ))
            ->add('otfFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.otfFile'),
                'required' => false
            ))
            ->add('svgFile', 'file', array(
                'label' => $this->getTranslator()->trans('font.svgFile'),
                'required' => false
            ));

        return $builder->getForm();
    }

    /**
     * @param \Symfony\Component\Form\Form $rawData
     *
     * @return RZ\Renzo\Core\Entities\Font
     */
    protected function addFont(\Symfony\Component\Form\Form $rawData)
    {

        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Font')
                    ->findOneBy(array('name' => $data['name'], 'variant' => $data['variant']));

            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.variant.already_exists"), 1);
            }

            $font = new Font();
            $font->setName($data['name']);
            $font->setHash($this->getKernel()->getConfig()['security']['secret']);
            $font->setVariant($data['variant']);

            $this->uploadFontFiles($rawData, $font);

            $this->getService('em')->persist($font);
            $this->getService('em')->flush();

            return $font;
        } else {
            throw new \RuntimeException("Font name is not defined", 1);
        }

        return null;
    }

    /**
     * Process font file uploads.
     * @param \Symfony\Component\Form\Form $data
     * @param RZ\Renzo\Core\Entities\Font  $font
     *
     * @return
     */
    protected function uploadFontFiles(\Symfony\Component\Form\Form $data, Font $font)
    {
        try {
            if (!empty($data['eotFile'])) {

                $eotFile = $data['eotFile']->getData();
                $uploadedEOTFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $eotFile['tmp_name'],
                    $eotFile['name'],
                    $eotFile['type'],
                    $eotFile['size'],
                    $eotFile['error']
                );
                if ($uploadedEOTFile !== null &&
                    $uploadedEOTFile->getError() == UPLOAD_ERR_OK &&
                    $uploadedEOTFile->isValid()) {

                    $font->setEOTFilename($uploadedEOTFile->getClientOriginalName());
                    $uploadedEOTFile->move(Font::getFilesFolder().'/'.$font->getFolder(), $font->getEOTFilename());
                }
            }
        } catch (FileNotFoundException $e) {
            // When empty file do nothing
        }
        try {
            if (!empty($data['woffFile'])) {

                $woffFile = $data['woffFile']->getData();
                $uploadedWOFFFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $woffFile['tmp_name'],
                    $woffFile['name'],
                    $woffFile['type'],
                    $woffFile['size'],
                    $woffFile['error']
                );
                if ($uploadedWOFFFile !== null &&
                    $uploadedWOFFFile->getError() == UPLOAD_ERR_OK &&
                    $uploadedWOFFFile->isValid()) {

                    $font->setWOFFFilename($uploadedWOFFFile->getClientOriginalName());
                    $uploadedWOFFFile->move(Font::getFilesFolder().'/'.$font->getFolder(), $font->getWOFFFilename());
                }
            }
        } catch (FileNotFoundException $e) {
            // When empty file do nothing
        }
        try {
            if (!empty($data['otfFile'])) {

                $otfFile = $data['otfFile']->getData();
                $uploadedOTFFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $otfFile['tmp_name'],
                    $otfFile['name'],
                    $otfFile['type'],
                    $otfFile['size'],
                    $otfFile['error']
                );
                if (null !== $uploadedOTFFile &&
                    $uploadedOTFFile->getError() == UPLOAD_ERR_OK &&
                    $uploadedOTFFile->isValid()) {

                    $font->setOTFFilename($uploadedOTFFile->getClientOriginalName());
                    $uploadedOTFFile->move(Font::getFilesFolder().'/'.$font->getFolder(), $font->getOTFFilename());
                }
            }
        } catch (FileNotFoundException $e) {
            // When empty file do nothing
        }
        try {
            if (!empty($data['svgFile'])) {

                $svgFile = $data['svgFile']->getData();
                $uploadedSVGFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $svgFile['tmp_name'],
                    $svgFile['name'],
                    $svgFile['type'],
                    $svgFile['size'],
                    $svgFile['error']
                );
                if (null !== $uploadedSVGFile &&
                    $uploadedSVGFile->getError() == UPLOAD_ERR_OK &&
                    $uploadedSVGFile->isValid()) {

                    $font->setSVGFilename($uploadedSVGFile->getClientOriginalName());
                    $uploadedSVGFile->move(Font::getFilesFolder().'/'.$font->getFolder(), $font->getSVGFilename());
                }
            }
        } catch (FileNotFoundException $e) {
            // When empty file do nothing
        }
    }

    /**
     * @param \Symfony\Component\Form\Form $rawData
     * @param RZ\Renzo\Core\Entities\Font  $font
     *
     * @return RZ\Renzo\Core\Entities\Font
     */
    protected function editFont(\Symfony\Component\Form\Form $rawData, Font $font)
    {
        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Font')
                    ->findOneBy(array('name' => $data['name'], 'variant' => $data['variant']));
            if ($existing !== null &&
                $existing->getId() != $font->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.name.already_exists"), 1);
            }

            $font->setName($data['name']);
            $font->setHash($this->getKernel()->getConfig()['security']['secret']);
            $font->setVariant($data['variant']);

            $this->uploadFontFiles($rawData, $font);

            $this->getService('em')->flush();

            return $font;
        } else {
            throw new \RuntimeException("Font name is not defined", 1);
        }

        return null;
    }

    /**
     * @param array                       $data
     * @param RZ\Renzo\Core\Entities\Font $font
     *
     * @return void
     */
    protected function deleteFont(array $data, Font $font)
    {
        $this->getService('em')->remove($font);
        $this->getService('em')->flush();
    }
}
