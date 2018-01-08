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
 * @file FontsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueFontVariant;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\FontType;
use Themes\Rozier\RozierApp;

/**
 * Fonts controller
 */
class FontsController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Font'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['fonts'] = $listManager->getEntities();

        return $this->render('fonts/list.html.twig', $this->assignation);
    }

    /**
     * Return an creation form for requested font.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = new Font();

        $form = $this->createForm(new FontType(), $font, [
            'em' => $this->get('em'),
            'constraints' => [
                new UniqueFontVariant([
                    'entityManager' => $this->get('em'),
                ]),
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->get('em')->persist($font);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('font.%name%.created', ['%name%' => $font->getName()]);
                $this->publishConfirmMessage($request, $msg);
            } catch (EntityAlreadyExistsException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('fontsHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('fonts/add.html.twig', $this->assignation);
    }

    /**
     * Return a deletion form for requested font.
     *
     * @param Request $request
     * @param int     $fontId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $fontId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

        if (null !== $font) {
            $form = $this->buildDeleteForm($font);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {
                try {
                    $this->get('em')->remove($font);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'font.%name%.deleted',
                        ['%name%' => $font->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityRequiredException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('fontsHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['font'] = $font;

            return $this->render('fonts/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an edition form for requested font.
     *
     * @param Request $request
     * @param int     $fontId
     *
     * @return Response
     */
    public function editAction(Request $request, $fontId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        /** @var Font $font */
        $font = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

        if ($font !== null) {
            $form = $this->createForm(new FontType(), $font, [
                'em' => $this->get('em'),
                'name' => $font->getName(),
                'variant' => $font->getVariant(),
                'constraints' => [
                    new UniqueFontVariant([
                        'entityManager' => $this->get('em'),
                        'currentName' => $font->getName(),
                        'currentVariant' => $font->getVariant(),
                    ]),
                ],
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    /*
                     * Force updating files if uploaded
                     * as doctrine wont see any changes.
                     */
                    $fontSubscriber = new FontLifeCycleSubscriber($this->getContainer());
                    $fontSubscriber->setFontFilesNames($font);
                    $fontSubscriber->upload($font);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'font.%name%.updated',
                        ['%name%' => $font->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                return $this->redirect($this->generateUrl('fontsHomePage'));
            }

            $this->assignation['font'] = $font;
            $this->assignation['form'] = $form->createView();

            return $this->render('fonts/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }
    /**
     * Return a ZipArchive of requested font.
     *
     * @param Request $request
     * @param int     $fontId
     *
     * @return Response
     */
    public function downloadAction(Request $request, $fontId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        /** @var Font $font */
        $font = $this->get('em')
                     ->find('RZ\Roadiz\Core\Entities\Font', (int) $fontId);

        /** @var Packages $packages */
        $packages = $this->get('assetPackages');

        if ($font !== null) {
            // Prepare File
            $filename = StringHandler::cleanForFilename($font->getName() . ' ' . $font->getReadableVariant()) . '.zip';
            $file = tempnam(sys_get_temp_dir(), $filename);
            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::OVERWRITE);

            if ("" != $font->getEOTFilename()) {
                $zip->addFile($packages->getFontsPath($font->getEOTRelativeUrl()), $font->getEOTFilename());
            }
            if ("" != $font->getSVGFilename()) {
                $zip->addFile($packages->getFontsPath($font->getSVGRelativeUrl()), $font->getSVGFilename());
            }
            if ("" != $font->getWOFFFilename()) {
                $zip->addFile($packages->getFontsPath($font->getWOFFRelativeUrl()), $font->getWOFFFilename());
            }
            if ("" != $font->getWOFF2Filename()) {
                $zip->addFile($packages->getFontsPath($font->getWOFF2RelativeUrl()), $font->getWOFF2Filename());
            }
            if ("" != $font->getOTFFilename()) {
                $zip->addFile($packages->getFontsPath($font->getOTFRelativeUrl()), $font->getOTFFilename());
            }
            // Close and send to users
            $zip->close();

            $response = new BinaryFileResponse(
                $file,
                Response::HTTP_OK,
                [
                    'content-control' => 'private',
                    'content-type' => 'application/zip',
                    'content-length' => filesize($file),
                    'content-disposition' => 'attachment; filename=' . $filename,
                ]
            );
            return $response;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Build delete font form with name constraint.
     *
     * @param Font $font
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Font $font)
    {
        $builder = $this->createFormBuilder()
                        ->add('fontId', 'hidden', [
                            'data' => $font->getId(),
                        ]);

        return $builder->getForm();
    }
}
