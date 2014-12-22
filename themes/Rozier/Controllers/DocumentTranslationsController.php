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
 * @file DocumentsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * {@inheritdoc}
 */
class DocumentTranslationsController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $documentId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null === $translationId) {
            $translation = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                    ->findDefault();

            $translationId = $translation->getId();
        } else {
            $translation = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        $this->assignation['available_translations'] = $this->getService('em')
                                                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                                            ->findAll();

        $document = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);
        $documentTr = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\DocumentTranslation')
            ->findOneBy(array('document'=>(int) $documentId, 'translation'=>(int) $translationId));

        if ($documentTr === null &&
            $document !== null &&
            $translation !== null) {
            $documentTr = $this->createDocumentTranslation($document, $translation);
        }

        if ($documentTr !== null &&
            $document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['translation'] = $translation;
            $this->assignation['documentTr'] = $documentTr;

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($documentTr);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editDocument($form->getData(), $documentTr);
                $msg = $this->getTranslator()->trans('document.translation.%name%.updated', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'documentsMetaPage',
                        array(
                            'documentId' => $document->getId(),
                            'translationId' => $translationId
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('document-translations/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Document    $document    [description]
     * @param Translation $translation [description]
     *
     * @return DocumentTranslation
     */
    protected function createDocumentTranslation(Document $document, Translation $translation)
    {
        $dt = new DocumentTranslation();
        $dt->setDocument($document);
        $dt->setTranslation($translation);

        $this->getService('em')->persist($dt);
        $this->getService('em')->flush();

        return $dt;
    }


    /**
     * Return an deletion form for requested document.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $documentId, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentTr = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\DocumentTranslation')
            ->findOneBy(array('document'=>(int) $documentId, 'translation'=>(int) $translationId));
        $document = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($documentTr !== null &&
            $document !== null) {
            $this->assignation['documentTr'] = $documentTr;
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($documentTr);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['documentId'] == $documentTr->getId()) {
                try {
                    $this->getService('em')->remove($documentTr);
                    $this->getService('em')->flush();

                    $msg = $this->getTranslator()->trans('document.translation.%name%.deleted', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('document.translation.%name%.cannot_delete', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->warning($msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'documentsEditPage',
                        array('documentId' => $document->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('document-translations/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\DocumentTranslation $doc
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(DocumentTranslation $doc)
    {
        $defaults = array(
            'documentTranslationId' => $doc->getId()
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('documentTranslationId', 'hidden', array(
                        'data' => $doc->getId(),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }
    /**
     * @param RZ\Roadiz\Core\Entities\DocumentTranslation $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(DocumentTranslation $document)
    {
        $defaults = array(
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'copyright' => $document->getCopyright()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'label' => $this->getTranslator()->trans('name'),
                        'required' => false
                    ))
                    ->add('description', new \RZ\Roadiz\CMS\Forms\MarkdownType(), array(
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ))
                    ->add('copyright', 'text', array(
                        'label' => $this->getTranslator()->trans('copyright'),
                        'required' => false
                    ));

        return $builder->getForm();
    }


    /**
     * @param array                                      $data
     * @param RZ\Roadiz\Core\Entities\DocumentTranslation $document
     */
    private function editDocument($data, DocumentTranslation $document)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $document->$setter($value);
        }

        $this->getService('em')->flush();
    }
}
