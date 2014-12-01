<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

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


            if ($this->getService('securityContext')->isGranted('ROLE_ACCESS_NODES_DELETE')) {
                /*
                 * Handle bulk delete form
                 */
                $deleteNodesForm = $this->buildBulkDeleteForm();
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

                $form = $this->buildBulkDeleteForm($nodesIds);
                $form->handleRequest();

                if ($form->isValid()) {
                    $msg = $this->bulkDeleteNodes($form->getData());

                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate('nodesHomePage')
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['nodes'] = $nodes;
                $this->assignation['form'] = $form->createView();

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
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDeleteForm($nodesIds = array())
    {
        $builder = $this->getService('formFactory')
                    ->createNamedBuilder('deleteForm')
                    ->add('nodesIds', 'hidden', array(
                        'data' => implode(',', $nodesIds),
                        'attr' => array('class' => 'nodes-id-bulk-tags'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

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
}
