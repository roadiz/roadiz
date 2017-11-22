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

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use RZ\Roadiz\CMS\Forms\NodeTypesType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraints\NotBlank;

trait NodesTrait
{
    /**
     * @param string $title
     * @param Translation $translation
     * @param Node|null $node
     * @param NodeType|null $type
     * @return Node
     */
    protected function createNode($title, Translation $translation, Node $node = null, NodeType $type = null)
    {
        $nodeName = StringHandler::slugify($title);
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('em');
        /** @var NodeRepository $repository */
        $repository = $entityManager->getRepository('RZ\Roadiz\Core\Entities\Node');

        if (true === $repository->exists($nodeName)) {
            $nodeName .= '-' . uniqid();
        }

        if ($node === null) {
            $node = new Node($type);
        }

        $node->setNodeName($nodeName);
        $entityManager->persist($node);

        $sourceClass = "GeneratedNodeSources\\" . $node->getNodeType()->getSourceEntityClassName();
        /** @var NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->setTitle($title);
        $source->setPublishedAt(new \DateTime());

        $entityManager->persist($source);
        $entityManager->flush();

        return $node;
    }

    /**
     * @param array $data
     * @param Node  $node
     *
     * @return NodeType|null
     */
    public function addStackType($data, Node $node)
    {
        if ($data['nodeId'] == $node->getId() &&
            !empty($data['nodeTypeId'])) {
            $nodeType = $this->get('em')
                             ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $data['nodeTypeId']);

            if (null !== $nodeType) {
                $node->addStackType($nodeType);
                $this->get('em')->flush();

                return $nodeType;
            }
        }

        return null;
    }

    /**
     * @param Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    public function buildStackTypesForm(Node $node)
    {
        if ($node->isHidingChildren()) {
            $defaults = [];

            $builder = $this->get('formFactory')
                            ->createBuilder('form', $defaults)
                            ->add('nodeId', 'hidden', [
                                'data' => (int) $node->getId(),
                            ])
                            ->add('nodeTypeId', new NodeTypesType($this->get('em'), true), [
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
     * @param Node $parentNode
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
                                    'entityManager' => $this->get('em'),
                                ]),
                            ],
                        ])
            ->add('nodeTypeId', new NodeTypesType($this->get('em')), [
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
     * @param Node $node
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
}
