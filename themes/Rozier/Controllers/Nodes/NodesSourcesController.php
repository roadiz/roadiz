<?php
/**
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
 * @file NodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * Nodes sources controller.
 *
 * {@inheritdoc}
 */
class NodesSourcesController extends RozierApp
{
    /**
     * @var bool
     */
    private $isReadOnly = false;

    /**
     * Return an edition form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     * @param int     $translationId
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function editSourceAction(Request $request, $nodeId, $translationId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId);

        /** @var Translation $translation */
        $translation = $this->get('em')->find(Translation::class, (int) $translationId);
        /*
         * Here we need to directly select nodeSource
         * if not doctrine will grab a cache tag because of NodeTreeWidget
         * that is initialized before calling route method.
         */
        /** @var Node $gnode */
        $gnode = $this->get('em')->find(Node::class, (int) $nodeId);

        if ($translation !== null && $gnode !== null) {
            /** @var NodesSources $source */
            $source = $this->get('em')
                           ->getRepository(NodesSources::class)
                           ->setDisplayingAllNodesStatuses(true)
                           ->setDisplayingNotPublishedNodes(true)
                           ->findOneBy(['translation' => $translation, 'node' => $gnode]);

            if (null !== $source) {
                $this->get('em')->refresh($source);
                $node = $source->getNode();
                $availableTranslations = $this->get('em')
                    ->getRepository(Translation::class)
                    ->findAvailableTranslationsForNode($gnode);

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $availableTranslations;
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                /**
                 * Versioning
                 */
                if ($this->isGranted('ROLE_ACCESS_VERSIONS')) {
                    if (null !== $response = $this->handleVersions($request, $source)) {
                        return $response;
                    }
                }

                $form = $this->createForm(
                    NodeSourceType::class,
                    $source,
                    [
                        'class' => $node->getNodeType()->getSourceEntityFullQualifiedClassName(),
                        'nodeType' => $node->getNodeType(),
                        'controller' => $this,
                        'entityManager' => $this->get('em'),
                        'container' => $this->getContainer(),
                        'withVirtual' => true,
                        'withTitle' => true,
                        'disabled' => $this->isReadOnly,
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted()) {
                    if ($form->isValid() && !$this->isReadOnly) {
                        /*
                         * Dispatch pre-flush event
                         */
                        $event = new FilterNodesSourcesEvent($source);
                        $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_PRE_UPDATE, $event);

                        $this->get('em')->flush();
                        /*
                         * Dispatch post-flush event
                         */
                        $event = new FilterNodesSourcesEvent($source);
                        $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_UPDATED, $event);

                        $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', [
                            '%node_source%' => $source->getNode()->getNodeName(),
                            '%translation%' => $source->getTranslation()->getName(),
                        ]);

                        $this->publishConfirmMessage($request, $msg, $source);

                        if ($request->isXmlHttpRequest()) {
                            $url = $this->generateUrl($source);
                            $previewUrl = '/preview.php' . str_replace('/dev.php', '', $url);

                            return new JsonResponse([
                                'status' => 'success',
                                'public_url' => $source->getNode()->isPublished() ? $url : $previewUrl,
                                'errors' => [],
                            ], JsonResponse::HTTP_PARTIAL_CONTENT);
                        }

                        return $this->redirect($this->generateUrl(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId' => $translation->getId()]
                        ));
                    }

                    if ($this->isReadOnly) {
                        $form->addError(new FormError('nodeSource.form.is_read_only'));
                    }

                    /*
                     * Handle errors when Ajax POST requests
                     */
                    if ($request->isXmlHttpRequest()) {
                        $errors = $this->getErrorsAsArray($form);
                        return new JsonResponse([
                            'status' => 'fail',
                            'errors' => $errors,
                            'message' => $this->getTranslator()->trans('form_has_errors.check_you_fields'),
                        ], JsonResponse::HTTP_BAD_REQUEST);
                    }
                }

                $this->assignation['form'] = $form->createView();
                $this->assignation['readOnly'] = $this->isReadOnly;

                return $this->render('nodes/editSource.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request      $request
     * @param NodesSources $nodesSources
     *
     * @return Response|null
     */
    protected function handleVersions(Request $request, NodesSources $nodesSources): ?Response
    {
        /**
         * Versioning.
         *
         * @var LogEntryRepository $repo
         */
        $repo = $this->get('em')->getRepository(LogEntry::class);
        $logs = $repo->getLogEntries($nodesSources);

        if ($request->get('version', null) !== null &&
            $request->get('version', null) > 0) {
            $versionNumber = (int) $request->get('version', null);
            $repo->revert($nodesSources, $versionNumber);
            $this->isReadOnly = true;
            $this->assignation['currentVersionNumber'] = $versionNumber;
            /** @var LogEntry $log */
            foreach ($logs as $log) {
                if ($log->getVersion() === $versionNumber) {
                    $this->assignation['currentVersion'] = $log;
                }
            }
            $revertForm = $this->createNamedFormBuilder('revertVersion')
                ->add('version', HiddenType::class, ['data' => $versionNumber])
                ->getForm();
            $revertForm->handleRequest($request);

            $this->assignation['revertForm'] = $revertForm->createView();

            if ($revertForm->isSubmitted() && $revertForm->isValid()) {
                $this->get('em')->persist($nodesSources);
                /*
                 * Dispatch pre-flush event
                 */
                $event = new FilterNodesSourcesEvent($nodesSources);
                $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_PRE_UPDATE, $event);
                $this->get('em')->flush();
                $event = new FilterNodesSourcesEvent($nodesSources);
                $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_UPDATED, $event);

                $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', [
                    '%node_source%' => $nodesSources->getNode()->getNodeName(),
                    '%translation%' => $nodesSources->getTranslation()->getName(),
                ]);

                $this->publishConfirmMessage($request, $msg, $nodesSources);

                return $this->redirect($this->generateUrl(
                    'nodesEditSourcePage',
                    [
                        'nodeId' => $nodesSources->getNode()->getId(),
                        'translationId' => $nodesSources->getTranslation()->getId()
                    ]
                ));
            }
        }

        $this->assignation['versions'] = $logs;

        return null;
    }

    /**
     * Return an remove form for requested nodeSource.
     *
     * @param Request $request
     * @param int     $nodeSourceId
     *
     * @return Response
     */
    public function removeAction(Request $request, $nodeSourceId)
    {
        /** @var NodesSources $ns */
        $ns = $this->get("em")->find(NodesSources::class, $nodeSourceId);
        if (null === $ns) {
            throw new ResourceNotFoundException();
        }
        /** @var Node $node */
        $node = $ns->getNode();
        $this->get("em")->refresh($ns->getNode());

        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_DELETE', $node->getId());

        /*
         * Prevent deleting last node-source available in node.
         */
        if ($node->getNodeSources()->count() <= 1) {
            $msg = $this->getTranslator()->trans('node_source.%node_source%.%translation%.cant.deleted', [
                '%node_source%' => $node->getNodeName(),
                '%translation%' => $ns->getTranslation()->getName(),
            ]);

            throw new BadRequestHttpException($msg);
        }

        $builder = $this->createFormBuilder()
                        ->add('nodeId', HiddenType::class, [
                            'data' => $nodeSourceId,
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Node $node */
            $node = $ns->getNode();
            /*
             * Dispatch event
             */
            $event = new FilterNodesSourcesEvent($ns);
            $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_DELETED, $event);

            $this->get("em")->remove($ns);
            $this->get("em")->flush();

            $ns = $node->getNodeSources()->first();

            $msg = $this->getTranslator()->trans('node_source.%node_source%.deleted.%translation%', [
                '%node_source%' => $node->getNodeName(),
                '%translation%' => $ns->getTranslation()->getName(),
            ]);

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'nodesEditSourcePage',
                ['nodeId' => $node->getId(), "translationId" => $ns->getTranslation()->getId()]
            ));
        }

        $this->assignation["nodeSource"] = $ns;
        $this->assignation['form'] = $form->createView();

        return $this->render('nodes/deleteSource.html.twig', $this->assignation);
    }
}
