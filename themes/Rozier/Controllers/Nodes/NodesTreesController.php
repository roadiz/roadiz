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
use Symfony\Component\HttpFoundation\Request;
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
    public function treeAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId, true);

        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($node);

        if (null !== $translationId) {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findOneBy(['id' => (int) $translationId]);
        } else {
            $translation = $this->getService('defaultTranslation');
        }

        if (null !== $node) {
            $widget = new NodeTreeWidget($request, $this, $node, $translation);

            if ($request->get('tagId') && $request->get('tagId') > 0) {
                $filterTag = $this->getService('em')
                                  ->find(
                                      '\RZ\Roadiz\Core\Entities\Tag',
                                      (int) $request->get('tagId')
                                  );

                $this->assignation['filterTag'] = $filterTag;

                $widget->setTag($filterTag);
            }

            $widget->setStackTree(true);
            $widget->getNodes(); //pre-fetch nodes for enable filters
            $this->assignation['node'] = $node;
            $this->assignation['source'] = $node->getNodeSources()->first();
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
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        if (!empty($request->get('deleteForm')['nodesIds'])) {
            $nodesIds = trim($request->get('deleteForm')['nodesIds']);
            $nodesIds = explode(',', $nodesIds);
            array_filter($nodesIds);

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            if (count($nodes) > 0) {
                $form = $this->buildBulkDeleteForm(
                    $request->get('deleteForm')['referer'],
                    $nodesIds
                );
                $form->handleRequest($request);

                if ($form->isValid()) {
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

        return $this->throw404();
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

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
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

                if ($form->isValid()) {
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

        return $this->throw404();
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
        $builder = $this->getService('formFactory')
                        ->createNamedBuilder('deleteForm')
                        ->add('nodesIds', 'hidden', [
                            'data' => implode(',', $nodesIds),
                            'attr' => ['class' => 'nodes-id-bulk-tags'],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        if (false !== $referer) {
            $builder->add('referer', 'hidden', [
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

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            foreach ($nodes as $node) {
                $node->getHandler()->softRemoveWithChildren();
            }

            $this->getService('em')->flush();

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

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            foreach ($nodes as $node) {
                $node->setStatus($data['status']);
            }

            $this->getService('em')->flush();

            return $this->getTranslator()->trans('nodes.bulk.status.changed');
        }

        return $this->getTranslator()->trans('wrong.request');
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkTagForm()
    {
        $builder = $this->getService('formFactory')
                        ->createNamedBuilder('tagForm')
                        ->add('nodesIds', 'hidden', [
                            'attr' => ['class' => 'nodes-id-bulk-tags'],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('tagsPaths', 'text', [
                            'label' => false,
                            'attr' => [
                                'class' => 'rz-tag-autocomplete',
                                'placeholder' => 'list.tags.to_link.or_unlink',
                            ],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('submitTag', 'submit', [
                            'label' => 'link.tags',
                            'attr' => [
                                'class' => 'uk-button uk-button-primary',
                                'title' => 'link.tags',
                                'data-uk-tooltip' => "{animation:true}",
                            ],
                        ])
                        ->add('submitUntag', 'submit', [
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

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            $paths = explode(',', $data['tagsPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                            ->findOrCreateByPath($path);

                foreach ($nodes as $node) {
                    $node->addTag($tag);
                }
            }
            $msg = $this->getTranslator()->trans('nodes.bulk.tagged');
        }

        $this->getService('em')->flush();

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

            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy([
                              'id' => $nodesIds,
                          ]);

            $paths = explode(',', $data['tagsPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                            ->findByPath($path);

                if (null !== $tag) {
                    foreach ($nodes as $node) {
                        $node->removeTag($tag);
                    }
                }
            }
            $msg = $this->getTranslator()->trans('nodes.bulk.untagged');
        }

        $this->getService('em')->flush();

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
        $builder = $this->getService('formFactory')
                        ->createNamedBuilder('statusForm')
                        ->add('nodesIds', 'hidden', [
                            'attr' => ['class' => 'nodes-id-bulk-status'],
                            'data' => implode(',', $nodesIds),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('status', 'choice', [
                            'label' => false,
                            'data' => $status,
                            'choices' => [
                                Node::DRAFT => 'draft',
                                Node::PENDING => 'pending',
                                Node::PUBLISHED => 'published',
                                Node::ARCHIVED => 'archived',
                            ],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        if (false !== $referer) {
            $builder->add('referer', 'hidden', [
                'data' => $referer,
            ]);
        }
        if (true === $submit) {
            $builder->add('submitStatus', 'submit', [
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
