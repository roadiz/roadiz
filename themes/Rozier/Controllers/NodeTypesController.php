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
 *
 *
 * @file NodeTypesController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
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
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\NodeType'
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

            $form = $this->buildEditForm($nodeType);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editNodeType($form->getData(), $nodeType);

                    $msg = $this->getTranslator()->trans('nodeType.%name%.updated', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        [
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION),
                        ]
                    )
                );
                $response->prepare($request);

                return $response->send();
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
            $form = $this->buildAddForm($nodeType);
            $form->handleRequest();
            if ($form->isValid()) {
                try {
                    $this->addNodeType($form->getData(), $nodeType);

                    $msg = $this->getTranslator()->trans('nodeType.%name%.created', ['%name%' => $nodeType->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesSchemaUpdate',
                            [
                                '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION),
                            ]
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
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

            $form->handleRequest();

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
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        [
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION),
                        ]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('node-types/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function editNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            if (isset($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.%name%.cannot_rename_already_exists', ['%name%' => $nodeType->getName()]), 1);
            }
            $setter = 'set' . ucwords($key);
            $nodeType->$setter($value);
        }

        $this->getService('em')->flush();
        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function addNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            $nodeType->$setter($value);
        }

        $existing = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                         ->findOneBy(['name' => $nodeType->getName()]);
        if ($existing !== null) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.%name%.already_exists', ['%name%' => $nodeType->getName()]), 1);
        }

        $this->getService('em')->persist($nodeType);
        $this->getService('em')->flush();

        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(NodeType $nodeType)
    {
        $defaults = [
            'name' => $nodeType->getName(),
            'displayName' => $nodeType->getDisplayName(),
            'description' => $nodeType->getDescription(),
            'visible' => $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' => $nodeType->isHidingNodes(),
            'color' => $nodeType->getColor(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add('name', 'text', [
                            'label' => $this->getTranslator()->trans('name'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(NodeType $nodeType)
    {
        $defaults = [
            'displayName' => $nodeType->getDisplayName(),
            'description' => $nodeType->getDescription(),
            'visible' => $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' => $nodeType->isHidingNodes(),
            'color' => $nodeType->getColor(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults);

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * Build common fields between add and edit node-type forms.
     *
     * @param FormBuilder $builder
     */
    private function buildCommonFormFields(&$builder)
    {
        $builder->add('displayName', 'text', [
                    'label' => $this->getTranslator()->trans('nodeType.displayName'),
                    'constraints' => [
                        new NotBlank(),
                    ],
                ])
                ->add('description', 'text', [
                    'label' => $this->getTranslator()->trans('description'),
                    'required' => false,
                ])
                ->add('visible', 'checkbox', [
                    'label' => $this->getTranslator()->trans('visible'),
                    'required' => false,
                ])
                ->add('newsletterType', 'checkbox', [
                    'label' => $this->getTranslator()->trans('nodeType.newsletterType'),
                    'required' => false,
                ])
                ->add('hidingNodes', 'checkbox', [
                    'label' => $this->getTranslator()->trans('nodeType.hidingNodes'),
                    'required' => false,
                ])
                ->add('color', 'text', [
                    'label' => $this->getTranslator()->trans('nodeType.color'),
                    'required' => false,
                    'attr' => ['class' => 'colorpicker-input'],
                ]);

        return $builder;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeType $nodeType)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('nodeTypeId', 'hidden', [
                            'data' => $nodeType->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getNewsletterNodeTypes()
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findBy(['newsletterType' => true]);
    }
}
