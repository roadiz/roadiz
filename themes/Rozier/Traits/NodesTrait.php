<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodesTrait.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Traits;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

trait NodesTrait
{
    /**
     * @param array                              $data
     * @param RZ\Roadiz\Core\Entities\NodeType    $type
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    protected function createNode($data, NodeType $type, Translation $translation)
    {
        $node = new Node($type);
        $node->setNodeName($data['nodeName']);
        $this->getService('em')->persist($node);

        $sourceClass = "GeneratedNodeSources\\" . $type->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);
        $source->setTitle($data['nodeName']);

        $this->getService('em')->persist($source);
        $this->getService('em')->flush();

        return $node;
    }

    /**
     * @param array       $data
     * @param Node        $parentNode
     * @param Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    protected function createChildNode($data, Node $parentNode = null, Translation $translation = null)
    {
        $type = $this->getService('em')
                     ->find(
                         'RZ\Roadiz\Core\Entities\NodeType',
                         (int) $data['nodeTypeId']
                     );

        if (null === $type) {
            throw new \Exception("Cannot create a node without a valid node-type", 1);
        }
        if (null !== $parentNode && $data['parentId'] != $parentNode->getId()) {
            throw new \Exception("Requested parent node does not match form values", 1);
        }

        $node = new Node($type);
        $node->setParent($parentNode);
        $node->setNodeName($data['nodeName']);
        $this->getService('em')->persist($node);

        $sourceClass = "GeneratedNodeSources\\" . $type->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);
        $source->setTitle($data['nodeName']);
        $this->getService('em')->persist($source);
        $this->getService('em')->flush();

        return $node;
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
     * Create a new node-source for given translation.
     *
     * @param array                        $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return void
     */
    protected function translateNode($data, Node $node)
    {
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

        /*
         * Dispatch event
         */
        $event = new FilterNodesSourcesEvent($source);
        $this->getService('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_CREATED, $event);
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildTranslateForm(Node $node)
    {
        $translations = $node->getHandler()->getUnavailableTranslations();
        $choices = [];

        foreach ($translations as $translation) {
            $choices[$translation->getId()] = $translation->getName();
        }

        if ($translations !== null && count($choices) > 0) {
            $builder = $this->getService('formFactory')
                            ->createBuilder('form')
                            ->add('nodeId', 'hidden', [
                                'data' => $node->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ])
                            ->add('translationId', 'choice', [
                                'label' => 'translation',
                                'choices' => $choices,
                                'required' => true,
                            ]);

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
            $defaults = [];

            $builder = $this->getService('formFactory')
                            ->createBuilder('form', $defaults)
                            ->add('nodeId', 'hidden', [
                                'data' => (int) $node->getId(),
                            ])
                            ->add('nodeTypeId', new \RZ\Roadiz\CMS\Forms\NodeTypesType(), [
                                'label' => 'nodeType',
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]);

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
    protected function buildAddChildForm(Node $parentNode = null)
    {
        $defaults = [];

        $builder = $this->createFormBuilder($defaults)
                        ->add('nodeName', 'text', [
                            'label' => 'nodeName',
                            'constraints' => [
                                new NotBlank(),
                                new UniqueNodeName([
                                    'entityManager' => $this->getService('em'),
                                ]),
                            ],
                        ])
            ->add('nodeTypeId', new \RZ\Roadiz\CMS\Forms\NodeTypesType(), [
                'label' => 'nodeType',
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        if (null !== $parentNode) {
            $builder->add('parentId', 'hidden', [
                'data' => (int) $parentNode->getId(),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDeleteForm(Node $node)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeId', 'hidden', [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEmptyTrashForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }

    /**
     * Generate node with given nodetype and translation
     *
     * @param Request  $request
     * @param NodeType          $nodeType
     * @param Node              $parent
     * @param Translation       $translation
     * @param Tag               $tag
     *
     * @return RZ\Roadiz\Core\Entities\NodeSource
     */
    public static function generateUniqueNodeWithTypeAndTranslation(
        Request $request,
        NodeType $nodeType,
        Node $parent,
        Translation $translation,
        Tag $tag = null
    ) {
        $name = $nodeType->getDisplayName() . " " . uniqid();

        $node = new Node($nodeType);
        $node->setParent($parent);
        $node->setNodeName($name);
        if (null !== $tag) {
            $node->addTag($tag);
        }
        Kernel::getService('em')->persist($node);

        if ($request->get('pushTop') == 1) {
            $node->setPosition(0.5);
        }

        $sourceClass = "GeneratedNodeSources\\" . $nodeType->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);
        $source->setTitle($name);
        Kernel::getService('em')->persist($source);
        Kernel::getService('em')->flush();

        return $source;
    }
}
