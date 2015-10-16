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
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\Forms\FontType;
use Themes\Rozier\RozierApp;

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
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_FONTS');

        $font = new Font();

        $form = $this->createForm(new FontType(), $font, [
            'em' => $this->getService('em'),
            'constraints' => [
                new UniqueFontVariant([
                    'entityManager' => $this->getService('em'),
                ]),
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->getService('em')->persist($font);
                $this->getService('em')->flush();

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
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['fontId'] == $font->getId()) {
                try {
                    $this->getService('em')->remove($font);
                    $this->getService('em')->flush();

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
            $form = $this->createForm(new FontType(), $font, [
                'em' => $this->getService('em'),
                'name' => $font->getName(),
                'variant' => $font->getVariant(),
                'constraints' => [
                    new UniqueFontVariant([
                        'entityManager' => $this->getService('em'),
                        'currentName' => $font->getName(),
                        'currentVariant' => $font->getVariant(),
                    ]),
                ],
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    /*
                     * need to force font upload if no changes
                     * has been made in entity fields
                     */
                    $font->preUpload();
                    $this->getService('em')->flush();
                    $font->upload();

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

            $filename = StringHandler::slugify($font->getName() . ' ' . $font->getReadableVariant()) . '.zip';

            $response = new Response(
                file_get_contents($file),
                Response::HTTP_OK,
                [
                    'content-control' => 'private',
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
     * Build delete font form with name constraint.
     * @param RZ\Roadiz\Core\Entities\Font $font
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
