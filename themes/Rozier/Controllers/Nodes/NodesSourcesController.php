<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Core\Routing\NodeRouter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\VersionedControllerTrait;
use Twig\Error\RuntimeError;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class NodesSourcesController extends RozierApp
{
    use VersionedControllerTrait;

    /**
     * Return an edition form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     * @param int     $translationId
     *
     * @return Response
     * @throws RuntimeError
     */
    public function editSourceAction(Request $request, int $nodeId, int $translationId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId);

        /** @var Translation $translation */
        $translation = $this->get('em')->find(Translation::class, $translationId);
        /*
         * Here we need to directly select nodeSource
         * if not doctrine will grab a cache tag because of NodeTreeWidget
         * that is initialized before calling route method.
         */
        /** @var Node $gnode */
        $gnode = $this->get('em')->find(Node::class, $nodeId);

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
                        'withVirtual' => true,
                        'withTitle' => true,
                        'disabled' => $this->isReadOnly,
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted()) {
                    if ($form->isValid() && !$this->isReadOnly) {
                        $this->onPostUpdate($source, $request);

                        if ($request->isXmlHttpRequest()) {
                            if ($this->get('settingsBag')->get('custom_preview_scheme')) {
                                $previewUrl = $this->generateUrl($source, [
                                    'canonicalScheme' => $this->get('settingsBag')->get('custom_preview_scheme'),
                                    NodeRouter::NO_CACHE_PARAMETER => true
                                ], Router::ABSOLUTE_URL);
                            } else {
                                $previewUrl = $this->generateUrl($source, [
                                    '_preview' => 1,
                                    NodeRouter::NO_CACHE_PARAMETER => true
                                ]);
                            }

                            if ($this->get('settingsBag')->get('custom_public_scheme')) {
                                $publicUrl = $this->generateUrl($source, [
                                    'canonicalScheme' => $this->get('settingsBag')->get('custom_public_scheme'),
                                    NodeRouter::NO_CACHE_PARAMETER => true
                                ], Router::ABSOLUTE_URL);
                            } else {
                                $publicUrl = $this->generateUrl($source, [
                                    NodeRouter::NO_CACHE_PARAMETER => true
                                ]);
                            }

                            return new JsonResponse([
                                'status' => 'success',
                                'public_url' => $source->getNode()->isPublished() ? $publicUrl : null,
                                'preview_url' => $previewUrl,
                                'errors' => [],
                            ], JsonResponse::HTTP_PARTIAL_CONTENT);
                        }

                        return $this->getPostUpdateRedirection($source);
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

                $availableTranslations = $this->get('em')
                    ->getRepository(Translation::class)
                    ->findAvailableTranslationsForNode($gnode);

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $availableTranslations;
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;
                $this->assignation['form'] = $form->createView();
                $this->assignation['readOnly'] = $this->isReadOnly;

                return $this->render('nodes/editSource.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an remove form for requested nodeSource.
     *
     * @param Request $request
     * @param int     $nodeSourceId
     *
     * @return Response
     * @throws RuntimeError
     */
    public function removeAction(Request $request, int $nodeSourceId)
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
                                new NotNull(),
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
            $this->get('dispatcher')->dispatch(new NodesSourcesDeletedEvent($ns));

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

    protected function onPostUpdate(AbstractEntity $entity, Request $request): void
    {
        /*
         * Dispatch pre-flush event
         */
        if ($entity instanceof NodesSources) {
            $this->get('dispatcher')->dispatch(new NodesSourcesPreUpdatedEvent($entity));
            $this->get('em')->flush();
            $this->get('dispatcher')->dispatch(new NodesSourcesUpdatedEvent($entity));

            $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', [
                '%node_source%' => $entity->getNode()->getNodeName(),
                '%translation%' => $entity->getTranslation()->getName(),
            ]);

            $this->publishConfirmMessage($request, $msg, $entity);
        }
    }

    protected function getPostUpdateRedirection(AbstractEntity $entity): ?Response
    {
        if ($entity instanceof NodesSources) {
            return $this->redirect($this->generateUrl(
                'nodesEditSourcePage',
                [
                    'nodeId' => $entity->getNode()->getId(),
                    'translationId' => $entity->getTranslation()->getId()
                ]
            ));
        }
        return null;
    }
}
