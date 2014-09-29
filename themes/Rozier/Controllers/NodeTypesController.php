<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file NodeTypesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
* NodeType controller
*/
class NodeTypesController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES');
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\NodeType'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['node_types'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('node-types/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an edition form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeTypeId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildEditForm($nodeType);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editNodeType($form->getData(), $nodeType);

                    $msg = $this->getTranslator()->trans('nodeType.updated', array('%name%'=>$nodeType->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        array(
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = new NodeType();

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            /*
             * form
             */
            $form = $this->buildAddForm($nodeType);
            $form->handleRequest();
            if ($form->isValid()) {
                try {
                    //echo "Before add node type";
                    $this->addNodeType($form->getData(), $nodeType);
                    //echo "After add node type";

                    $msg = $this->getTranslator()->trans('nodeType.created', array('%name%'=>$nodeType->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesSchemaUpdate',
                            array(
                                '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                            )
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesAddPage'
                        )
                    );
                }
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeTypeId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES_DELETE');

        $nodeType = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildDeleteForm($nodeType);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeTypeId'] == $nodeType->getId() ) {

                /*
                 * Delete All node-type association and schema
                 */
                $nodeType->getHandler()->deleteWithAssociations();

                $msg = $this->getTranslator()->trans('nodeType.deleted', array('%name%'=>$nodeType->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        array(
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function editNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            if (isset($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.cannot_rename_already_exists', array('%name%'=>$nodeType->getName())), 1);
            }
            $setter = 'set'.ucwords($key);
            $nodeType->$setter( $value );
        }

        $this->getService('em')->flush();
        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function addNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $nodeType->$setter( $value );
        }

        $existing = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\NodeType')
            ->findOneBy(array('name'=>$nodeType->getName()));
        if ($existing !== null) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.already_exists', array('%name%'=>$nodeType->getName())), 1);
        }

        $this->getService('em')->persist($nodeType);
        $this->getService('em')->flush();

        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(NodeType $nodeType)
    {
        $defaults = array(
            'name' =>           $nodeType->getName(),
            'displayName' =>    $nodeType->getDisplayName(),
            'description' =>    $nodeType->getDescription(),
            'visible' =>        $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' =>    $nodeType->isHidingNodes(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )))
            ->add('displayName', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )))
            ->add('description', 'text', array('required' => false))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('newsletterType', 'checkbox', array('required' => false))
            ->add('hidingNodes', 'checkbox', array('required' => false));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(NodeType $nodeType)
    {
        $defaults = array(
            'displayName' =>    $nodeType->getDisplayName(),
            'description' =>    $nodeType->getDescription(),
            'visible' =>        $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' =>    $nodeType->isHidingNodes(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('displayName', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )))
            ->add('description', 'text', array('required' => false))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('newsletterType', 'checkbox', array('required' => false))
            ->add('hidingNodes', 'checkbox', array('required' => false));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeType $nodeType)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeTypeId', 'hidden', array(
                'data' => $nodeType->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getNewsletterNodeTypes()
    {
        return $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\NodeType')
            ->findBy(array('newsletterType' => true));
    }
}
