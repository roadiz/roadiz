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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\CMS\Forms\SeparatorType;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Translation\Translator;

/**
 * Nodes controller
 *
 * {@inheritdoc}
 */
class NodesController extends RozierApp
{
    /**
     * List every nodes.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $filter = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $translation = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();


        switch ($filter) {
            case 'draft':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = array(
                    'status' => Node::DRAFT
                );
                break;
            case 'pending':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = array(
                    'status' => Node::PENDING
                );
                break;
            case 'archived':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = array(
                    'status' => Node::ARCHIVED
                );
                break;
            case 'deleted':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = array(
                    'status' => Node::DELETED
                );
                break;

            default:

                $this->assignation['mainFilter'] = 'all';
                $arrayFilter = array();
                break;
        }
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Node',
            $arrayFilter
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['nodes'] = $listManager->getEntities();
        $this->assignation['nodeTypes'] = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findBy(array('newsletterType' => false));
        $this->assignation['translation'] = $translation;

        return new Response(
            $this->getTwig()->render('nodes/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an edition form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_SETTING');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($node);

        $translation = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findDefault();

        if (null !== $node) {
            $this->assignation['node'] = $node;
            $this->assignation['source'] = $node->getNodeSources()->first();
            $this->assignation['translation'] = $translation;

            /*
             * Handle translation form
             */
            $translationForm = $this->buildTranslateForm($node);
            if (null !== $translationForm) {
                $translationForm->handleRequest();

                if ($translationForm->isValid()) {

                    try {
                        $this->translateNode($translationForm->getData(), $node);
                        $msg = $this->getTranslator()->trans('node.%name%.translated', array(
                            '%name%'=>$node->getNodeName()
                        ));
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);
                    } catch (EntityAlreadyExistsException $e) {
                        $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                        $this->getService('logger')->warning($e->getMessage());
                    }
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            array('nodeId' => $node->getId(), 'translationId'=>$translationForm->getData()['translationId'])
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
                $this->assignation['translationForm'] = $translationForm->createView();
            }

            /*
             * Handle StackTypes form
             */
            $stackTypesForm = $this->buildStackTypesForm($node);
            if (null !== $stackTypesForm) {
                $stackTypesForm->handleRequest();

                if ($stackTypesForm->isValid())
                {
                    try {
                        $type = $this->addStackType($stackTypesForm->getData(), $node);
                        $msg = $this->getTranslator()->trans('stack_node.%name%.has_new_type.%type%', array(
                            '%name%'=>$node->getNodeName(),
                            '%type%'=>$type->getDisplayName()
                        ));
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);
                    } catch (EntityAlreadyExistsException $e) {
                        $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                        $this->getService('logger')->warning($e->getMessage());
                    }
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            array('nodeId' => $node->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
                $this->assignation['stackTypesForm'] = $stackTypesForm->createView();
            }

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($node);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editNode($form->getData(), $node);
                    $msg = $this->getTranslator()->trans('node.%name%.updated', array(
                        '%name%'=>$node->getNodeName()
                    ));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodesEditPage',
                        array('nodeId' => $node->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }
            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('nodes/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        }

        return $this->throw404();
    }

    /**
     * Return tags form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editTagsAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $translation = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findDefault();

        if (null !== $translation) {

            $source = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                ->findOneBy(array(
                    'translation'=>$translation,
                    'node.id'=>(int) $nodeId
                ));

            if (null !== $source &&
                null !== $translation) {

                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                $form = $this->buildEditTagsForm($node);

                $form->handleRequest();

                if ($form->isValid()) {
                    $this->addNodeTag($form->getData(), $node);

                    $msg = $this->getTranslator()->trans('node.%node%.linked.tags', array(
                        '%node%'=>$node->getNodeName()
                    ));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditTagsPage',
                            array('nodeId' => $node->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['form'] = $form->createView();

                return new Response(
                    $this->getTwig()->render('nodes/editTags.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );
            }
        }

        return $this->throw404();
    }

    /**
     * Return a deletion form for requested tag depending on the node.
     *
     * @param Symfony\Component\HttpFoundation\Requet $request
     * @param int                                     $nodeId
     * @param int                                     $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function removeTagAction(Request $request, $nodeId, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $tag = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if ($node !== null && $tag !== null) {
            $this->assignation['node'] = $node;
            $this->assignation['tag'] = $tag;

            $form = $this->buildRemoveTagForm($node, $tag);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->removeNodeTag($form->getData(), $node, $tag);
                $msg = $this->getTranslator()->trans('tag.%name%.removed', array('%name%' => $tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodesEditTagsPage',
                        array('nodeId' => $node->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('nodes/removeTag.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle node creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $type = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        $translation = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        if ($translationId != null) {
            $translation = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($type !== null &&
            $translation !== null) {

            $form = $this->getService('formFactory')
                ->createBuilder()
                ->add('nodeName', 'text', array(
                    'constraints' => array(
                        new NotBlank()
                    )
                ))
                ->getForm();
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $node = $this->createNode($form->getData(), $type, $translation);

                    $msg = $this->getTranslator()->trans('node.%name%.created', array('%name%'=>$node->getNodeName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            array('nodeId' => $node->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {

                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesAddPage',
                            array('nodeTypeId' => $nodeTypeId, 'translationId' => $translationId)
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['type'] = $type;

            return new Response(
                $this->getTwig()->render('nodes/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle node creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addChildAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $translation = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findDefault();

        if (null !== $translationId) {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($nodeId > 0) {
            $parentNode = $this->getService('em')
                               ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        }
        else $parentNode = null;

        if (null !== $translation) {

            $form = $this->buildAddChildForm($parentNode, $translation);
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $node = $this->createChildNode($form->getData(), $parentNode, $translation);

                    $msg = $this->getTranslator()->trans('node.%name%.created', array('%name%'=>$node->getNodeName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            array('nodeId' => $node->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {

                    $request->getSession()->getFlashBag()->add(
                        'error',
                        $e->getMessage()
                    );
                    $this->getService('logger')->warning($e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesAddChildPage',
                            array('nodeId' => $nodeId, 'translationId' => $translationId)
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['parentNode'] = $parentNode;

            return new Response(
                $this->getTwig()->render('nodes/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node &&
            !$node->isDeleted() &&
            !$node->isLocked()) {

            $this->assignation['node'] = $node;

            $form = $this->buildDeleteForm($node);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeId'] == $node->getId()) {

                $node->getHandler()->softRemoveWithChildren();
                $this->getService('em')->flush();

                // Update Solr Search engine if setup
                if (true === $this->getKernel()->pingSolrServer()) {

                    foreach ($node->getNodeSources() as $nodeSource) {
                        $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                            $nodeSource,
                            $this->getService('solr')
                        );
                        $solrSource->getDocumentFromIndex();
                        $solrSource->updateAndCommit();
                    }
                }

                $msg = $this->getTranslator()->trans('node.%name%.deleted', array('%name%'=>$node->getNodeName()));
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

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('nodes/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }


    public function emptyTrashAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        $form = $this->buildEmptyTrashForm();
        $form->handleRequest();

        if ($form->isValid()){
            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy(array(
                             'status' => Node::DELETED
                          ));

            foreach ($nodes as $node) {
                $node->getHandler()->removeWithChildrenAndAssociations();
            }

            $msg = $this->getTranslator()->trans('node.trash.emptied');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('nodesHomeDeletedPage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('nodes/emptyTrash.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }
    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function undeleteAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node &&
            $node->isDeleted()) {

            $this->assignation['node'] = $node;

            $form = $this->buildDeleteForm($node);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeId'] == $node->getId()) {

                $node->getHandler()->softUnremoveWithChildren();
                $this->getService('em')->flush();

                // Update Solr Search engine if setup
                if (true === $this->getKernel()->pingSolrServer()) {

                    foreach ($node->getNodeSources() as $nodeSource) {
                        $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                            $nodeSource,
                            $this->getService('solr')
                        );
                        $solrSource->getDocumentFromIndex();
                        $solrSource->updateAndCommit();
                    }
                }

                $msg = $this->getTranslator()->trans('node.%name%.undeleted', array('%name%'=>$node->getNodeName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('nodesEditPage', array(
                        'nodeId' => $node->getId()
                    ))
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('nodes/undelete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                              $data
     * @param RZ\Roadiz\Core\Entities\NodeType    $type
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    private function createNode($data, NodeType $type, Translation $translation)
    {
        if ($this->urlAliasExists(StringHandler::slugify($data['nodeName']))) {
            $msg = $this->getTranslator()->trans(
                'node.%name%.no_creation.urlAlias.alreadyExists',
                array('%name%'=>$data['nodeName'])
            );

            throw new EntityAlreadyExistsException($msg, 1);
        }
        if ($this->nodeNameExists(StringHandler::slugify($data['nodeName']))) {
            $msg = $this->getTranslator()->trans(
                'node.%name%.no_creation.already_exists',
                array('%name%'=>$data['nodeName'])
            );

            throw new EntityAlreadyExistsException($msg, 1);
        }

        try {
            $node = new Node($type);
            $node->setNodeName($data['nodeName']);
            $this->getService('em')->persist($node);

            $sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
            $source = new $sourceClass($node, $translation);
            $source->setTitle($data['nodeName']);

            $this->getService('em')->persist($source);
            $this->getService('em')->flush();

            return $node;
        } catch (\Exception $e) {
            $msg = $this->getTranslator()->trans('node.%name%.noCreation.alreadyExists', array('%name%'=>$node->getNodeName()));
            throw new EntityAlreadyExistsException($msg, 1);
        }
    }

    /**
     * @param array       $data
     * @param Node        $parentNode
     * @param Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    private function createChildNode($data, Node $parentNode = null, Translation $translation = null)
    {
        if ($this->urlAliasExists(StringHandler::slugify($data['nodeName']))) {
            $msg = $this->getTranslator()->trans('node.%name%.no_creation.url_alias.already_exists', array('%name%'=>$data['nodeName']));

            throw new EntityAlreadyExistsException($msg, 1);
        }
        if ($this->nodeNameExists(StringHandler::slugify($data['nodeName']))) {
            $msg = $this->getTranslator()->trans('node.%name%.no_creation.already_exists', array('%name%'=>$data['nodeName']));

            throw new EntityAlreadyExistsException($msg, 1);
        }
        $type = null;

        if (!empty($data['nodeTypeId'])) {
            $type = $this->getService('em')
                        ->find(
                            'RZ\Roadiz\Core\Entities\NodeType',
                            (int) $data['nodeTypeId']
                        );
        }
        if (null === $type) {
            throw new \Exception("Cannot create a node without a valid node-type", 1);
        }
        if (null !== $parentNode && $data['parentId'] != $parentNode->getId()) {
            throw new \Exception("Requested parent node does not match form values", 1);
        }

        try {
            $node = new Node($type);
            $node->setParent($parentNode);
            $node->setNodeName($data['nodeName']);
            $this->getService('em')->persist($node);

            $sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
            $source = new $sourceClass($node, $translation);
            $source->setTitle($data['nodeName']);
            $this->getService('em')->persist($source);
            $this->getService('em')->flush();

            return $node;
        } catch (\Exception $e) {
            $msg = $this->getTranslator()->trans('node.%name%.no_creation.alreadyExists', array('%name%'=>$node->getNodeName()));

            throw new EntityAlreadyExistsException($msg, 1);
        }
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    private function urlAliasExists($name)
    {
        return (boolean) $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
            ->exists($name);
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    private function nodeNameExists($name)
    {
        return (boolean) $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->exists($name);
    }

    /**
     * Edit node base parameters.
     *
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     */
    private function editNode($data, Node $node)
    {
        $testingNodeName = StringHandler::slugify($data['nodeName']);
        if ($testingNodeName != $node->getNodeName() &&
                ($this->nodeNameExists($testingNodeName) ||
                $this->urlAliasExists($testingNodeName))) {

            $msg = $this->getTranslator()->trans('node.%name%.noUpdate.alreadyExists', array('%name%'=>$data['nodeName']));
            throw new EntityAlreadyExistsException($msg, 1);
        }
        foreach ($data as $key => $value) {

            if ($key == 'home' && $value == true) {
                $node->getHandler()->makeHome();
            } else {
                $setter = 'set'.ucwords($key);
                $node->$setter( $value );
            }
        }

        $this->getService('em')->flush();
    }

    /**
     * @param array $data
     * @param Node  $node
     */
    public function addStackType($data, Node $node)
    {
        if ($data['nodeId'] == $node->getId() &&
            !empty($data['nodeTypeId'])) {

            $nodeType = $this->getService('em')
                 ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $data['nodeTypeId']);

            if (null !== $nodeType) {

                $node->addStackType($nodeType);
                $this->getService('em')->flush();

                return $nodeType;
            }
        }

        return null;
    }

    /**
     * Link a node with a tag.
     *
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return RZ\Roadiz\Core\Entities\Tag $linkedTag
     */
    private function addNodeTag($data, Node $node)
    {
        if (!empty($data['tagPaths'])) {
            $paths = explode(',', $data['tagPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                            ->findOrCreateByPath($path);

                $node->addTag($tag);
            }
        }

        $this->getService('em')->flush();

        return $tag;
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param RZ\Roadiz\Core\Entities\Tag  $tag
     *
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    private function removeNodeTag($data, Node $node, Tag $tag)
    {
        if ($data['nodeId'] == $node->getId() &&
            $data['tagId'] == $tag->getId()) {

            $node->removeTag($tag);
            $this->getService('em')->flush();

            return $tag;
        }
    }

    /**
     * Create a new node-source for given translation.
     *
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return void
     */
    private function translateNode($data, Node $node)
    {
        $sourceClass = "GeneratedNodeSources\\".$node
                                ->getNodeType()
                                ->getSourceEntityClassName();

        $newTranslation = $this->getService('em')
                ->find(
                    'RZ\Roadiz\Core\Entities\Translation',
                    (int) $data['translationId']
                );

        $baseSource = $node->getNodeSources()->first();

        $source = clone $baseSource;

        foreach ($source->getDocumentsByFields() as $document) {
           $this->getService('em')->persist($document);
        }
        $source->setTranslation($newTranslation);
        $source->setNode($node);

        $this->getService('em')->persist($source);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildTranslateForm(Node $node)
    {
        $translations = $node->getHandler()->getUnavailableTranslations();
        $choices = array();

        foreach ($translations as $translation) {
            $choices[$translation->getId()] = $translation->getName();
        }

        if ($translations !== null && count($choices) > 0) {

            $builder = $this->getService('formFactory')
                ->createBuilder('form')
                ->add('nodeId', 'hidden', array(
                    'data' => $node->getId(),
                    'constraints' => array(
                        new NotBlank()
                    )
                ))
                ->add('translationId', 'choice', array(
                    'label' => $this->getTranslator()->trans('translation'),
                    'choices' => $choices,
                    'required' => true
                ));

            return $builder->getForm();
        } else {
            return null;
        }
    }
    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    public function buildStackTypesForm(Node $node)
    {
        if ($node->isHidingChildren()) {
            $defaults = array();

            $builder = $this->getService('formFactory')
                ->createBuilder('form', $defaults)
                ->add('nodeId', 'hidden', array(
                    'data'=>(int) $node->getId()
                ))
                ->add('nodeTypeId', new \RZ\Roadiz\CMS\Forms\NodeTypesType(), array(
                    'label' => $this->getTranslator()->trans('nodeType'),
                ));

            return $builder->getForm();
        } else {
            return null;
        }
    }


    /**
     * @param RZ\Roadiz\Core\Entities\Node $parentNode
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddChildForm(Node $parentNode = null)
    {
        $defaults = array();

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('nodeName', 'text', array(
                'label' => $this->getTranslator()->trans('nodeName'),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('nodeTypeId', new \RZ\Roadiz\CMS\Forms\NodeTypesType(), array(
                'label' => $this->getTranslator()->trans('nodeType'),
            ));

        if (null !== $parentNode) {
            $builder->add('parentId', 'hidden', array(
                'data'=>(int) $parentNode->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));
        }

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node  $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Node $node)
    {
        $fields = $node->getNodeType()->getFields();

        $defaults = array(
            'nodeName' => $node->getNodeName(),
            'home' => $node->isHome(),
            'priority' => $node->getPriority(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add(
                'nodeName',
                'text',
                array(
                    'label' => $this->getTranslator()->trans('nodeName'),
                    'constraints' => array(new NotBlank())
                )
            )
            ->add(
                'priority',
                'number',
                array(
                    'label' => $this->getTranslator()->trans('priority'),
                    'constraints' => array(new NotBlank())
                )
            )
            ->add(
                'home',
                'checkbox',
                array(
                    'label' => $this->getTranslator()->trans('node.isHome'),
                    'required' => false,
                    'attr' => array('class' => 'rz-boolean-checkbox')
                )
            );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditTagsForm(Node $node)
    {
        $defaults = array(
            'nodeId' =>  $node->getId()
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('nodeId', 'hidden', array(
                        'data' => $node->getId(),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('tagPaths', 'text', array(
                        'label' => $this->getTranslator()->trans('list.tags.to_link'),
                        'attr' => array('class' => 'rz-tag-autocomplete')
                    ))
                    ->add('separator_1', new SeparatorType(), array(
                        'label' => $this->getTranslator()->trans('use.new_or_existing.tags_with_hierarchy'),
                        'attr' => array('class' => 'form-help-static uk-alert uk-alert-large')
                    ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Node $node)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeId', 'hidden', array(
                'data' => $node->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildEmptyTrashForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form');

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param RZ\Roadiz\Core\Entities\Tag  $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildRemoveTagForm(Node $node, Tag $tag)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeId', 'hidden', array(
                'data' => $node->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('tagId', 'hidden', array(
                'data' => $tag->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
