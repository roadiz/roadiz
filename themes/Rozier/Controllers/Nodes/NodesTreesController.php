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
 * @file NodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Widgets\NodeTreeWidget;

/**
 * Nodes trees controller
 *
 * {@inheritdoc}
 */
class NodesTreesController extends RozierApp
{
    /**
     * @param Request $request
     * @param int     $nodeId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function treeAction(Request $request, $nodeId = null, $translationId = null)
    {
        if ($nodeId > 0) {
            $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId, true);
            /** @var Node $node */
            $node = $this->get('em')->find(Node::class, (int) $nodeId);

            if (null === $node) {
                throw new ResourceNotFoundException();
            }

            $this->get('em')->refresh($node);
        } elseif (null !== $this->getUser()) {
            $node = $this->getUser()->getChroot();
        } else {
            $node = null;
        }

        if (null !== $translationId) {
            /** @var Translation $translation */
            $translation = $this->get('em')
                                ->getRepository(Translation::class)
                                ->findOneBy(['id' => (int) $translationId]);
        } else {
            /** @var Translation $translation */
            $translation = $this->get('defaultTranslation');
        }

        $widget = new NodeTreeWidget($request, $this, $node, $translation);

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
            $this->assignation['source'] = $node->getNodeSourcesByTranslation($translation)->first();
        }
        $this->assignation['translation'] = $translation;
        $this->assignation['specificNodeTree'] = $widget;

        /*
         * Handle bulk tag form
         */
        $tagNodesForm = $this->buildBulkTagForm();
        $tagNodesForm->handleRequest($request);
        if ($tagNodesForm->isValid()) {
            $data = $tagNodesForm->getData();

            if ($tagNodesForm->get('submitTag')->isClicked()) {
                $msg = $this->tagNodes($data);
            } elseif ($tagNodesForm->get('submitUntag')->isClicked()) {
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bulkStatusAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_STATUS');

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
                    (int) $request->get('statusForm')['status'],
                    false
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
     * @param bool  $referer
     * @param array $nodesIds
     *
     * @return \Symfony\Component\Form\Form
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
                                new NotBlank(),
                            ],
                        ]);

        if (false !== $referer) {
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
    private function bulkDeleteNodes($data)
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
    private function bulkStatusNodes($data)
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
                          ]);

            foreach ($nodes as $node) {
                $node->setStatus($data['status']);
            }

            $this->get('em')->flush();

            return $this->getTranslator()->trans('nodes.bulk.status.changed');
        }

        return $this->getTranslator()->trans('wrong.request');
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkTagForm()
    {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
                        ->createNamedBuilder('tagForm')
                        ->add('nodesIds', HiddenType::class, [
                            'attr' => ['class' => 'nodes-id-bulk-tags'],
                            'constraints' => [
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
                        ]);

        return $builder->getForm();
    }

    /**
     * @param  array $data
     * @return string
     */
    private function tagNodes($data)
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
    private function untagNodes($data)
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
     * @param bool  $referer
     * @param array $nodesIds
     * @param int   $status
     * @param bool  $submit
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkStatusForm(
        $referer = false,
        $nodesIds = [],
        $status = Node::DRAFT,
        $submit = true
    ) {
        /** @var FormBuilder $builder */
        $builder = $this->get('formFactory')
                        ->createNamedBuilder('statusForm')
                        ->add('nodesIds', HiddenType::class, [
                            'attr' => ['class' => 'nodes-id-bulk-status'],
                            'data' => implode(',', $nodesIds),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('status', ChoiceType::class, [
                            'label' => false,
                            'data' => $status,
                            'choices_as_values' => true,
                            'choices' => [
                                Node::getStatusLabel(Node::DRAFT) => Node::DRAFT,
                                Node::getStatusLabel(Node::PENDING) => Node::PENDING,
                                Node::getStatusLabel(Node::PUBLISHED) => Node::PUBLISHED,
                                Node::getStatusLabel(Node::ARCHIVED) => Node::ARCHIVED,
                            ],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        if (false !== $referer) {
            $builder->add('referer', HiddenType::class, [
                'data' => $referer,
            ]);
        }
        if (true === $submit) {
            $builder->add('submitStatus', SubmitType::class, [
                'label' => 'change.nodes.status',
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                    'title' => 'change.nodes.status',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ]);
        }

        return $builder->getForm();
    }
}
