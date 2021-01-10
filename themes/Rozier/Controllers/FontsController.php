<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\EntityRequiredException;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\FontType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_FONTS');

        $listManager = $this->createEntityListManager(
            Font::class,
            [],
            ['name' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_FONTS');

        $font = new Font();

        $form = $this->createForm(FontType::class, $font);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_FONTS');

        $font = $this->get('em')->find(Font::class, (int) $fontId);

        if (null !== $font) {
            $form = $this->buildDeleteForm($font);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_FONTS');

        /** @var Font $font */
        $font = $this->get('em')->find(Font::class, (int) $fontId);

        if ($font !== null) {
            $form = $this->createForm(FontType::class, $font, [
                'name' => $font->getName(),
                'variant' => $font->getVariant(),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_FONTS');

        /** @var Font $font */
        $font = $this->get('em')->find(Font::class, (int) $fontId);

        if ($font !== null) {
            // Prepare File
            $file = tempnam(sys_get_temp_dir(), "font_" . $font->getId());
            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::CREATE);

            /** @var Packages $packages */
            $packages = $this->get('assetPackages');

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
            $filename = StringHandler::slugify($font->getName() . ' ' . $font->getReadableVariant()) . '.zip';

            return new BinaryFileResponse($file, Response::HTTP_OK, [
                'content-type' => 'application/zip',
                'content-disposition' => 'attachment; filename=' . $filename,
            ], false);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Build delete font form with name constraint.
     *
     * @param Font $font
     *
     * @return FormInterface
     */
    protected function buildDeleteForm(Font $font)
    {
        $builder = $this->createFormBuilder()
                        ->add('fontId', HiddenType::class, [
                            'data' => $font->getId(),
                        ]);

        return $builder->getForm();
    }
}
