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

use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Font'
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
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $form = $this->buildAddForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $font = $this->addFont($form); // only pass form for file handling

                $msg = $this->getTranslator()->trans('font.%name%.created', array('%name%'=>$font->getName()));
                $this->publishConfirmMessage($request, $msg);

            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
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
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

        if (null !== $font) {
            $form = $this->buildDeleteForm($font);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {

                try {
                    $this->deleteFont($form->getData(), $font);
                    $msg = $this->getTranslator()->trans(
                        'font.%name%.deleted',
                        array('%name%'=>$font->getName())
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityRequiredException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('fontsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['font'] = $font;

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
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

        if ($font !== null) {

            $form = $this->buildEditForm($font);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {

                try {
                    $this->editFont($form, $font); // only pass form for file handling
                    $msg = $this->getTranslator()->trans(
                        'font.%name%.updated',
                        array('%name%'=>$font->getName())
                    );
                    $this->publishConfirmMessage($request, $msg);

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('fontsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['font'] = $font;
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
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

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
            if ("" != $font->getWOFF2Filename()) {
                $zip->addFile($font->getWOFF2AbsolutePath(), $font->getWOFF2Filename());
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
                    'content-control' => 'private',
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
        $builder = $this->getService('formFactory')
            ->createBuilder('form');

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * Build delete font form with name constraint.
     * @param RZ\Roadiz\Core\Entities\Font $font
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Font $font)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('font_id', 'hidden', array(
                'data'=>$font->getId()
            ));

        return $builder->getForm();
    }

    /**
     * Build edit font form with name constraint.
     * @param RZ\Roadiz\Core\Entities\Font $font
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditForm(Font $font)
    {
        $defaults = array(
            'name'=>$font->getName(),
            'variant'=>$font->getVariant()
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('fontId', 'hidden', array(
                'data'=>$font->getId()
            ));

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * Build common fields between add and edit font forms.
     *
     * @param FormBuilder $builder
     */
    private function buildCommonFormFields(&$builder)
    {
        $builder->add('name', 'text', array(
            'label' => $this->getTranslator()->trans('font.name'),
        ))
        ->add('eotFile', 'file', array(
            'label' => $this->getTranslator()->trans('font.eotFile'),
            'required' => false
        ))
        ->add('svgFile', 'file', array(
            'label' => $this->getTranslator()->trans('font.svgFile'),
            'required' => false
        ))
        ->add('otfFile', 'file', array(
            'label' => $this->getTranslator()->trans('font.otfFile'),
            'required' => false
        ))
        ->add('woffFile', 'file', array(
            'label' => $this->getTranslator()->trans('font.woffFile'),
            'required' => false
        ))
        ->add('woff2File', 'file', array(
            'label' => $this->getTranslator()->trans('font.woff2File'),
            'required' => false
        ))
        ->add(
            'variant',
            new \RZ\Roadiz\CMS\Forms\FontVariantsType(),
            array(
                'label' => $this->getTranslator()->trans('font.variant')
            )
        );

        return $builder;
    }

    /**
     * @param \Symfony\Component\Form\Form $rawData
     *
     * @return RZ\Roadiz\Core\Entities\Font
     */
    protected function addFont(\Symfony\Component\Form\Form $rawData)
    {

        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Font')
                    ->findOneBy(array('name' => $data['name'], 'variant' => $data['variant']));

            if ($existing !== null) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.variant.already_exists"), 1);
            }

            $font = new Font();
            $font->setName($data['name']);
            $font->setHash($this->getService('config')['security']['secret']);
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
     * @param RZ\Roadiz\Core\Entities\Font  $font
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
            if (!empty($data['woff2File'])) {

                $woff2File = $data['woff2File']->getData();
                $uploadedWOFF2File = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $woff2File['tmp_name'],
                    $woff2File['name'],
                    $woff2File['type'],
                    $woff2File['size'],
                    $woff2File['error']
                );
                if ($uploadedWOFF2File !== null &&
                    $uploadedWOFF2File->getError() == UPLOAD_ERR_OK &&
                    $uploadedWOFF2File->isValid()) {

                    $font->setWOFF2Filename($uploadedWOFF2File->getClientOriginalName());
                    $uploadedWOFF2File->move(
                        Font::getFilesFolder().'/'.$font->getFolder(),
                        $font->getWOFF2Filename()
                    );
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
     * @param RZ\Roadiz\Core\Entities\Font  $font
     *
     * @return RZ\Roadiz\Core\Entities\Font
     */
    protected function editFont(\Symfony\Component\Form\Form $rawData, Font $font)
    {
        $data = $rawData->getData();

        if (isset($data['name'])) {
            $existing = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Font')
                    ->findOneBy(array(
                        'name' => $data['name'],
                        'variant' => $data['variant']
                    ));
            if ($existing !== null &&
                $existing->getId() != $font->getId()) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.name.already_exists"), 1);
            }

            $font->setName($data['name']);
            $font->setHash($this->getService('config')['security']['secret']);
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
     * @param RZ\Roadiz\Core\Entities\Font $font
     *
     * @return void
     */
    protected function deleteFont(array $data, Font $font)
    {
        $this->getService('em')->remove($font);
        $this->getService('em')->flush();
    }
}
