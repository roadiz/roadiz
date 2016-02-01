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
 * @file NodeTypesController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers\NodeTypes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\NodeTypeType;
use Themes\Rozier\RozierApp;

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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\NodeType',
            [],
            ['name' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['node_types'] = $listManager->getEntities();

        return $this->render('node-types/list.html.twig', $this->assignation);
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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->createForm(new NodeTypeType(), $nodeType, [
                'em' => $this->getService('em'),
                'name' => $nodeType->getName(),
            ]);

            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->getService('em')->flush();
                    $nodeType->getHandler()->updateSchema();

                    $msg = $this->getTranslator()->trans('nodeType.%name%.updated', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'nodeTypesSchemaUpdate',
                    [
                        '_token' => $this->getService('csrfTokenManager')->getToken(static::SCHEMA_TOKEN_INTENTION),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/edit.html.twig', $this->assignation);
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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = new NodeType();

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            /*
             * form
             */
            $form = $this->createForm(new NodeTypeType(), $nodeType, [
                'em' => $this->getService('em'),
            ]);

            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $this->getService('em')->persist($nodeType);
                    $this->getService('em')->flush();
                    $nodeType->getHandler()->updateSchema();

                    $msg = $this->getTranslator()->trans('nodeType.%name%.created', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl(
                        'nodeTypesSchemaUpdate',
                        [
                            '_token' => $this->getService('csrfTokenManager')->getToken(static::SCHEMA_TOKEN_INTENTION),
                        ]
                    ));

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                    return $this->redirect($this->generateUrl(
                        'nodeTypesAddPage'
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/add.html.twig', $this->assignation);
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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES_DELETE');

        $nodeType = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildDeleteForm($nodeType);

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['nodeTypeId'] == $nodeType->getId()) {
                /*
                 * Delete All node-type association and schema
                 */
                $nodeType->getHandler()->deleteWithAssociations();

                $msg = $this->getTranslator()->trans('nodeType.%name%.deleted', ['%name%' => $nodeType->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'nodeTypesSchemaUpdate',
                    [
                        '_token' => $this->getService('csrfTokenManager')->getToken(static::SCHEMA_TOKEN_INTENTION),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeType $nodeType)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeTypeId', 'hidden', [
                            'data' => $nodeType->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
