<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Workflow\Workflow;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Widgets\TreeWidgetFactory;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class NodesTreesController extends RozierApp
{
    /**
     * @param Request $request
     * @param int|null $nodeId
     * @param int|null $translationId
     *
     * @return Response
     */
    public function treeAction(Request $request, ?int $nodeId = null, ?int $translationId = null)
    {
        if (null !== $nodeId) {
            $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId, true);
            /** @var Node|null $node */
            $node = $this->get('em')->find(Node::class, $nodeId);

            if (null === $node) {
                throw new ResourceNotFoundException();
            }

            $this->get('em')->refresh($node);
        } elseif (null !== $this->getUser()) {
            $node = $this->get(NodeChrootResolver::class)->getChroot($this->getUser());
        } else {
            $node = null;
        }

        if (null !== $translationId) {
            /** @var Translation $translation */
            $translation = $this->get('em')
                                ->getRepository(Translation::class)
                                ->findOneBy(['id' => $translationId]);
        } else {
            /** @var Translation $translation */
            $translation = $this->get('defaultTranslation');
        }

        $widget = $this->get(TreeWidgetFactory::class)->createNodeTree($node, $translation);

        if ($request->get('tagId') &&
            $request->get('tagId') > 0) {
            $filterTag = $this->get('em')->find(Tag::class, (int) $request->get('tagId'));
            $this->assignation['filterTag'] = $filterTag;
            $widget->setTag($filterTag);
        }

        $widget->setStackTree(true);
        $widget->getNodes(); //pre-fetch nodes for enable filters

        if (null !== $node) {
            $this->assignation['node'] = $node;

            if ($node->isHidingChildren()) {
                $this->assignation['availableTags'] = $this->get('em')->getRepository(Tag::class)->findAllLinkedToNodeChildren(
                    $node,
                    $translation
                );
            }
            $this->assignation['source'] = $node->getNodeSourcesByTranslation($translation)->first();
            $availableTranslations = $this->get('em')
                ->getRepository(Translation::class)
                ->findAvailableTranslationsForNode($node);
            $this->assignation['available_translations'] = $availableTranslations;
        }
        $this->assignation['translation'] = $translation;
        $this->assignation['specificNodeTree'] = $widget;

        /*
         * Handle bulk tag form
         */
        $tagNodesForm = $this->buildBulkTagForm();
        $tagNodesForm->handleRequest($request);
        if ($tagNodesForm->isSubmitted() && $tagNodesForm->isValid()) {
            $data = $tagNodesForm->getData();

            $submitTag = $tagNodesForm->get('submitTag');
            $submitUntag = $tagNodesForm->get('submitUntag');
            if ($submitTag instanceof ClickableInterface && $submitTag->isClicked()) {
                $msg = $this->tagNodes($data);
            } elseif ($submitUntag instanceof ClickableInterface && $submitUntag->isClicked()) {
                $msg = $this->untagNodes($data);
            } else {
                $msg = $this->getTranslator()->trans('wrong.request');
            }

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'nodesTreePage',
                ['nodeId' => $nodeId, 'translationId' => $translationId]
            ));
        }
        $this->assignation['tagNodesForm'] = $tagNodesForm->createView();

        /*
         * Handle bulk status
         */
        if ($this->isGranted('ROLE_ACCESS_NODES_STATUS')) {
            $statusBulkNodes = $this->buildBulkStatusForm($request->getRequestUri());
            $this->assignation['statusNodesForm'] = $statusBulkNodes->createView();
        }

        if ($this->isGranted('ROLE_ACCESS_NODES_DELETE')) {
            /*
             * Handle bulk delete form
             */
            $deleteNodesForm = $this->buildBulkDeleteForm($request->getRequestUri());
            $this->assignation['deleteNodesForm'] = $deleteNodesForm->createView();
        }

        return $this->render('nodes/tree.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES_DELETE');

        if (!empty($request->get('deleteForm')['nodesIds'])) {
            $nodesIds = trim($request->get('deleteForm')['nodesIds']);
            $nodesIds = explode(',', $nodesIds);
            array_filter($nodesIds);

            /** @var Node[] $nodes */
            $nodes = $this->get('em')
                          ->getRepository(Node::class)
                          ->setDisplayingNotPublishedNodes(true)
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            if (count($nodes) > 0) {
                $form = $this->buildBulkDeleteForm(
                    $request->get('deleteForm')['referer'],
                    $nodesIds
                );
                $form->handleRequest($request);
                if ($request->get('confirm') && $form->isSubmitted() && $form->isValid()) {
                    $msg = $this->bulkDeleteNodes($form->getData());
                    $this->publishConfirmMessage($request, $msg);

                    if (!empty($form->getData()['referer'])) {
                        return $this->redirect($form->getData()['referer']);
                    } else {
                        return $this->redirect($this->generateUrl('nodesHomePage'));
                    }
                }

                $this->assignation['nodes'] = $nodes;
                $this->assignation['form'] = $form->createView();

                if (!empty($request->get('deleteForm')['referer'])) {
                    $this->assignation['referer'] = $request->get('deleteForm')['referer'];
                }

                return $this->render('nodes/bulkDelete.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function bulkStatusAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES_STATUS');

        if (!empty($request->get('statusForm')['nodesIds'])) {
            $nodesIds = trim($request->get('statusForm')['nodesIds']);
            $nodesIds = explode(',', $nodesIds);
            array_filter($nodesIds);

            /** @var Node[] $nodes */
            $nodes = $this->get('em')
                          ->getRepository(Node::class)
                          ->setDisplayingNotPublishedNodes(true)
                          ->findBy([
                              'id' => $nodesIds,
                          ]);
            if (count($nodes) > 0) {
                $form = $this->buildBulkStatusForm(
                    $request->get('statusForm')['referer'],
                    $nodesIds,
                    (string) $request->get('statusForm')['status']
                );

                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $msg = $this->bulkStatusNodes($form->getData());
                    $this->publishConfirmMessage($request, $msg);

                    if (!empty($form->getData()['referer'])) {
                        return $this->redirect($form->getData()['referer']);
                    } else {
                        return $this->redirect($this->generateUrl('nodesHomePage'));
                    }
                }

                $this->assignation['nodes'] = $nodes;
                $this->assignation['form'] = $form->createView();

                if (!empty($request->get('statusForm')['referer'])) {
                    $this->assignation['referer'] = $request->get('statusForm')['referer'];
                }

                return $this->render('nodes/bulkStatus.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param false|string  $referer
     * @param array $nodesIds
     *
     * @return FormInterface
     */
    private function buildBulkDeleteForm(
        $referer = false,
        $nodesIds = []
    ) {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
                        ->createNamedBuilder('deleteForm')
                        ->add('nodesIds', HiddenType::class, [
                            'data' => implode(',', $nodesIds),
                            'attr' => ['class' => 'nodes-id-bulk-tags'],
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        if (false !== $referer && (new UnicodeString($referer))->startsWith('/')) {
            $builder->add('referer', HiddenType::class, [
                'data' => $referer,
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function bulkDeleteNodes(array $data)
    {
        if (!empty($data['nodesIds'])) {
            $nodesIds = trim($data['nodesIds']);
            $nodesIds = explode(',', $nodesIds);
            array_filter($nodesIds);

            $nodes = $this->get('em')
                          ->getRepository(Node::class)
                          ->setDisplayingNotPublishedNodes(true)
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            /** @var Node $node */
            foreach ($nodes as $node) {
                /** @var NodeHandler $handler */
                $handler = $this->get('factory.handler')->getHandler($node);
                $handler->softRemoveWithChildren();
            }

            $this->get('em')->flush();

            return $this->getTranslator()->trans('nodes.bulk.deleted');
        }

        return $this->getTranslator()->trans('wrong.request');
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function bulkStatusNodes(array $data)
    {
        if (!empty($data['nodesIds'])) {
            $nodesIds = trim($data['nodesIds']);
            $nodesIds = explode(',', $nodesIds);
            array_filter($nodesIds);

            /** @var Node[] $nodes */
            $nodes = $this->get('em')
                ->getRepository(Node::class)
                ->setDisplayingNotPublishedNodes(true)
                ->findBy([
                    'id' => $nodesIds,
                ])
            ;

            foreach ($nodes as $node) {
                /** @var Workflow $workflow */
                $workflow = $this->get('workflow.registry')->get($node);
                if ($workflow->can($node, $data['status'])) {
                    $workflow->apply($node, $data['status']);
                }
            }
            $this->get('em')->flush();
            return $this->getTranslator()->trans('nodes.bulk.status.changed');
        }

        return $this->getTranslator()->trans('wrong.request');
    }

    /**
     * @return FormInterface
     */
    private function buildBulkTagForm()
    {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
            ->createNamedBuilder('tagForm')
            ->add('nodesIds', HiddenType::class, [
                'attr' => ['class' => 'nodes-id-bulk-tags'],
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('tagsPaths', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'rz-tag-autocomplete',
                    'placeholder' => 'list.tags.to_link.or_unlink',
                ],
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('submitTag', SubmitType::class, [
                'label' => 'link.tags',
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                    'title' => 'link.tags',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ])
            ->add('submitUntag', SubmitType::class, [
                'label' => 'unlink.tags',
                'attr' => [
                    'class' => 'uk-button',
                    'title' => 'unlink.tags',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ])
        ;

        return $builder->getForm();
    }

    /**
     * @param  array $data
     * @return string
     */
    private function tagNodes(array $data)
    {
        $msg = $this->getTranslator()->trans('nodes.bulk.not_tagged');

        if (!empty($data['tagsPaths']) &&
            !empty($data['nodesIds'])) {
            $nodesIds = explode(',', $data['nodesIds']);
            $nodesIds = array_filter($nodesIds);

            /** @var Node[] $nodes */
            $nodes = $this->get('em')
                          ->getRepository(Node::class)
                          ->setDisplayingNotPublishedNodes(true)
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            $paths = explode(',', $data['tagsPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->get('em')
                            ->getRepository(Tag::class)
                            ->findOrCreateByPath($path);

                foreach ($nodes as $node) {
                    $node->addTag($tag);
                }
            }
            $msg = $this->getTranslator()->trans('nodes.bulk.tagged');
        }

        $this->get('em')->flush();

        return $msg;
    }

    /**
     * @param  array $data
     * @return string
     */
    private function untagNodes(array $data)
    {
        $msg = $this->getTranslator()->trans('nodes.bulk.not_untagged');

        if (!empty($data['tagsPaths']) &&
            !empty($data['nodesIds'])) {
            $nodesIds = explode(',', $data['nodesIds']);
            $nodesIds = array_filter($nodesIds);

            /** @var Node[] $nodes */
            $nodes = $this->get('em')
                          ->getRepository(Node::class)
                          ->setDisplayingNotPublishedNodes(true)
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            $paths = explode(',', $data['tagsPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->get('em')
                            ->getRepository(Tag::class)
                            ->findByPath($path);

                if (null !== $tag) {
                    foreach ($nodes as $node) {
                        $node->removeTag($tag);
                    }
                }
            }
            $msg = $this->getTranslator()->trans('nodes.bulk.untagged');
        }

        $this->get('em')->flush();

        return $msg;
    }

    /**
     * @param false|string  $referer
     * @param array $nodesIds
     * @param string $status
     *
     * @return FormInterface
     */
    private function buildBulkStatusForm(
        $referer = false,
        $nodesIds = [],
        $status = 'reject'
    ) {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
            ->createNamedBuilder('statusForm')
            ->add('nodesIds', HiddenType::class, [
                'attr' => ['class' => 'nodes-id-bulk-status'],
                'data' => implode(',', $nodesIds),
                'constraints' => [
                    new NotBlank(),
                    new NotNull(),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => false,
                'data' => $status,
                'choices' => [
                    Node::getStatusLabel(Node::DRAFT) => 'reject',
                    Node::getStatusLabel(Node::PENDING) => 'review',
                    Node::getStatusLabel(Node::PUBLISHED) => 'publish',
                    Node::getStatusLabel(Node::ARCHIVED) => 'archive',
                ],
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
        ;

        if (false !== $referer && (new UnicodeString($referer))->startsWith('/')) {
            $builder->add('referer', HiddenType::class, [
                'data' => $referer,
            ]);
        }

        return $builder->getForm();
    }
}
