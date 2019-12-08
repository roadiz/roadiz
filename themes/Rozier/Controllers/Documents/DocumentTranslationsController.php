<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DocumentTranslationsController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\Controllers\Documents;

use Exception;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DocumentTranslationUpdatedEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\DocumentTranslationType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\VersionedControllerTrait;
use Twig\Error\RuntimeError;

/**
 * Class DocumentTranslationsController
 * @package Themes\Rozier\Controllers\Documents
 */
class DocumentTranslationsController extends RozierApp
{
    use VersionedControllerTrait;

    /**
     * @param Request $request
     * @param int     $documentId
     * @param int     $translationId
     *
     * @return Response
     * @throws RuntimeError
     */
    public function editAction(Request $request, $documentId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        if (null === $translationId) {
            $translation = $this->get('defaultTranslation');

            $translationId = $translation->getId();
        } else {
            $translation = $this->get('em')
                                ->find(Translation::class, (int) $translationId);
        }

        $this->assignation['available_translations'] = $this->get('em')
             ->getRepository(Translation::class)
             ->findAll();

        /** @var Document $document */
        $document = $this->get('em')
                         ->find(Document::class, (int) $documentId);
        $documentTr = $this->get('em')
                           ->getRepository(DocumentTranslation::class)
                           ->findOneBy(['document' => (int) $documentId, 'translation' => (int) $translationId]);

        if ($documentTr === null && $document !== null && $translation !== null) {
            $documentTr = $this->createDocumentTranslation($document, $translation);
        }

        if ($documentTr !== null && $document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['translation'] = $translation;
            $this->assignation['documentTr'] = $documentTr;

            /**
             * Versioning
             */
            if ($this->isGranted('ROLE_ACCESS_VERSIONS')) {
                if (null !== $response = $this->handleVersions($request, $documentTr)) {
                    return $response;
                }
            }

            /*
             * Handle main form
             */
            $form = $this->createForm(DocumentTranslationType::class, $documentTr, [
                'referer' => $this->get('requestStack')->getCurrentRequest()->get('referer'),
                'disabled' => $this->isReadOnly,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans('document.translation.%name%.updated', [
                    '%name%' => $document->getFilename(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                $this->get("dispatcher")->dispatch(
                    new DocumentTranslationUpdatedEvent($document)
                );

                $routeParams = [
                    'documentId' => $document->getId(),
                    'translationId' => $translationId,
                ];

                if ($form->get('referer')->getData()) {
                    $routeParams = array_merge($routeParams, [
                        'referer' => $form->get('referer')->getData()
                    ]);
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'documentsMetaPage',
                    $routeParams
                ));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['readOnly'] = $this->isReadOnly;

            return $this->render('document-translations/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
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

        $this->get('em')->persist($dt);

        return $dt;
    }

    /**
     * Return an deletion form for requested document.
     *
     * @param Request $request
     * @param int     $documentId
     * @param int     $translationId
     *
     * @return Response
     * @throws RuntimeError
     */
    public function deleteAction(Request $request, $documentId, $translationId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentTr = $this->get('em')
                           ->getRepository(DocumentTranslation::class)
                           ->findOneBy(['document' => (int) $documentId, 'translation' => (int) $translationId]);
        $document = $this->get('em')
                         ->find(Document::class, (int) $documentId);

        if ($documentTr !== null &&
            $document !== null) {
            $this->assignation['documentTr'] = $documentTr;
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($documentTr);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['documentId'] == $documentTr->getId()) {
                try {
                    $this->get('em')->remove($documentTr);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans('document.translation.%name%.deleted', ['%name%' => $document->getFilename()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (Exception $e) {
                    $msg = $this->getTranslator()->trans('document.translation.%name%.cannot_delete', ['%name%' => $document->getFilename()]);
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->get('logger')->warning($msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'documentsEditPage',
                    ['documentId' => $document->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('document-translations/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param DocumentTranslation $doc
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildDeleteForm(DocumentTranslation $doc)
    {
        $defaults = [
            'documentTranslationId' => $doc->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('documentTranslationId', HiddenType::class, [
                            'data' => $doc->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param AbstractEntity $entity
     * @param Request        $request
     */
    protected function onPostUpdate(AbstractEntity $entity, Request $request): void
    {
        /*
         * Dispatch pre-flush event
         */
        if ($entity instanceof DocumentTranslation) {
            $this->get('em')->flush();
            $msg = $this->getTranslator()->trans('document.translation.%name%.updated', [
                '%name%' => $entity->getDocument()->getFilename(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            $this->get("dispatcher")->dispatch(
                new DocumentTranslationUpdatedEvent($entity->getDocument())
            );
        }
    }

    /**
     * @param AbstractEntity $entity
     *
     * @return Response
     */
    protected function getPostUpdateRedirection(AbstractEntity $entity): ?Response
    {
        if ($entity instanceof DocumentTranslation) {
            $routeParams = [
                'documentId' => $entity->getDocument()->getId(),
                'translationId' => $entity->getTranslation()->getId(),
            ];
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl(
                'documentsMetaPage',
                $routeParams
            ));
        }
        return null;
    }
}
