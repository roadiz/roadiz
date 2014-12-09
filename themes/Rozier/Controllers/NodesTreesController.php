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
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Nodes trees controller
 *
 * {@inheritdoc}
 */
class NodesTreesController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function treeAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($node);

        $translation = null;
        if (null !== $translationId) {
            $translation = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findOneBy(array('id'=>(int) $translationId));
        } else {
            $translation = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                    ->findDefault();
        }

        if (null !== $node) {

            $widget = new NodeTreeWidget($request, $this, $node, $translation);
            $widget->setStackTree(true);
            $widget->getNodes(); //pre-fetch nodes for enable filters
            $this->assignation['node'] =             $node;
            $this->assignation['source'] =           $node->getNodeSources()->first();
            $this->assignation['translation'] =      $translation;
            $this->assignation['specificNodeTree'] = $widget;

            /*
             * Handle bulk tag form
             */
            $tagNodesForm = $this->buildBulkTagForm();
            $tagNodesForm->handleRequest();
            if ($tagNodesForm->isValid()) {
                $data = $tagNodesForm->getData();

                if ($tagNodesForm->get('submitTag')->isClicked()) {
                    $msg = $this->tagNodes($data);
                } elseif ($tagNodesForm->get('submitUntag')->isClicked()) {
                    $msg = $this->untagNodes($data);
                } else {
                    $msg = $this->getTranslator()->trans('wrong.request');
                }

                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodesTreePage',
                        array('nodeId' => $nodeId, 'translationId' => $translationId)
                    )
                );
                $response->prepare($request);

                return $response->send();
            }
            $this->assignation['tagNodesForm'] = $tagNodesForm->createView();


            /*
             * Handle bulk status
             */
            if ($this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES_STATUS')) {

                $statusBulkNodes = $this->buildBulkStatusForm($request->getRequestUri());
                $this->assignation['statusNodesForm'] = $statusBulkNodes->createView();
            }

            if ($this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES_DELETE')) {
                /*
                 * Handle bulk delete form
                 */
                $deleteNodesForm = $this->buildBulkDeleteForm($request->getRequestUri());
                $this->assignation['deleteNodesForm'] = $deleteNodesForm->createView();
            }


            return new Response(
                $this->getTwig()->render('nodes/tree.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));

            if (count($nodes) > 0) {

                $form = $this->buildBulkDeleteForm(
                    $request->get('deleteForm')['referer'],
                    $nodesIds
                );
                $form->handleRequest();

                if ($form->isValid()) {
                    $msg = $this->bulkDeleteNodes($form->getData());

                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    if (!empty($form->getData()['referer'])) {
                        $response = new RedirectResponse($form->getData()['referer']);
                    } else {
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate('nodesHomePage')
                        );
                    }
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['nodes'] = $nodes;
                $this->assignation['form'] = $form->createView();

                if (!empty($request->get('deleteForm')['referer'])) {
                    $this->assignation['referer'] = $request->get('deleteForm')['referer'];
                }

                return new Response(
                    $this->getTwig()->render('nodes/bulkDelete.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );
            }
        }

        return $this->throw404();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));

            if (count($nodes) > 0) {

                $form = $this->buildBulkStatusForm(
                    $request->get('statusForm')['referer'],
                    $nodesIds,
                    (int) $request->get('statusForm')['status'],
                    false
                );

                $form->handleRequest();

                if ($form->isValid()) {
                    $msg = $this->bulkStatusNodes($form->getData());

                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);


                    if (!empty($form->getData()['referer'])) {
                        $response = new RedirectResponse($form->getData()['referer']);
                    } else {
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate('nodesHomePage')
                        );
                    }
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['nodes'] = $nodes;
                $this->assignation['form'] = $form->createView();

                if (!empty($request->get('statusForm')['referer'])) {
                    $this->assignation['referer'] = $request->get('statusForm')['referer'];
                }

                return new Response(
                    $this->getTwig()->render('nodes/bulkStatus.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );
            }
        }

        return $this->throw404();
    }


    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDeleteForm(
        $referer = false,
        $nodesIds = array()
    ) {
        $builder = $this->getService('formFactory')
                    ->createNamedBuilder('deleteForm')
                    ->add('nodesIds', 'hidden', array(
                        'data' => implode(',', $nodesIds),
                        'attr' => array('class' => 'nodes-id-bulk-tags'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        if (false !== $referer) {
            $builder->add('referer', 'hidden', array(
                'data' => $referer,
            ));
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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));

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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));

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
                    ->add('nodesIds', 'hidden', array(
                        'attr' => array('class' => 'nodes-id-bulk-tags'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('tagsPaths', 'text', array(
                        'label' => false,
                        'attr' => array(
                            'class' => 'rz-tag-autocomplete',
                            'placeholder' => $this->getTranslator()->trans('list.tags.to_link.or_unlink')
                        ),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('submitTag', 'submit', array(
                        'label' => $this->getTranslator()->trans('link.tags'),
                        'attr' => array(
                            'class' => 'uk-button uk-button-primary',
                            'title' => $this->getTranslator()->trans('link.tags'),
                            'data-uk-tooltip' => "{animation:true}"
                        )
                    ))
                    ->add('submitUntag', 'submit', array(
                        'label' => $this->getTranslator()->trans('unlink.tags'),
                        'attr' => array(
                            'class' => 'uk-button',
                            'title' => $this->getTranslator()->trans('unlink.tags'),
                            'data-uk-tooltip' => "{animation:true}"
                        )
                    ));

        return $builder->getForm();
    }

    /**
     * [tagNodes description]
     * @param  [type] $data [description]
     * @return [type]       [description]
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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));


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
     * [untagNodes description]
     * @param  [type] $data [description]
     * @return [type]       [description]
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
                            ->findBy(array(
                                'id' => $nodesIds
                            ));


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
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkStatusForm(
        $referer = false,
        $nodesIds = array(),
        $status = Node::DRAFT,
        $submit = true
    ) {
        $builder = $this->getService('formFactory')
                    ->createNamedBuilder('statusForm')
                    ->add('nodesIds', 'hidden', array(
                        'attr' => array('class' => 'nodes-id-bulk-status'),
                        'data' => implode(',', $nodesIds),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('status', 'choice', array(
                        'label' => false,
                        'data' => $status,
                        'choices' => array(
                            Node::DRAFT => $this->getTranslator()->trans('draft'),
                            Node::PENDING => $this->getTranslator()->trans('pending'),
                            Node::PUBLISHED => $this->getTranslator()->trans('published'),
                            Node::ARCHIVED => $this->getTranslator()->trans('archived')
                        ),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        if (false !== $referer) {
            $builder->add('referer', 'hidden', array(
                'data' => $referer,
            ));
        }
        if (true === $submit) {

            $builder->add('submitStatus', 'submit', array(
                'label' => $this->getTranslator()->trans('change.nodes.status'),
                'attr' => array(
                    'class' => 'uk-button uk-button-primary',
                    'title' => $this->getTranslator()->trans('change.nodes.status'),
                    'data-uk-tooltip' => "{animation:true}"
                )
            ));
        }

        return $builder->getForm();
    }
}
