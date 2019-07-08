<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodesAttributesController.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Attribute\Form\AttributeValueTranslationType;
use RZ\Roadiz\Attribute\Form\AttributeValueType;
use RZ\Roadiz\Core\Entities\AttributeValue;
use RZ\Roadiz\Core\Entities\AttributeValueTranslation;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class NodesAttributesController extends RozierApp
{
    /**
     * @param Request $request
     * @param         $nodeId
     * @param         $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeId, $translationId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODE_ATTRIBUTES');

        /** @var Translation $translation */
        $translation = $this->get('em')->find(Translation::class, (int) $translationId);
        /** @var Node $node */
        $node = $this->get('em')->find(Node::class, (int) $nodeId);

        if (null === $translation || null === $node) {
            throw $this->createNotFoundException('Node-source does not exist');
        }

        /** @var NodesSources $nodeSource */
        $nodeSource = $this->get('em')
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findOneBy(['translation' => $translation, 'node' => $node]);

        if (null === $nodeSource) {
            throw $this->createNotFoundException('Node-source does not exist');
        }

        $availableTranslations = $this->get('em')
            ->getRepository(Translation::class)
            ->findAvailableTranslationsForNode($node);

        if (null !== $response = $this->handleAddAttributeForm($request, $node, $translation)) {
            return $response;
        }

        $this->assignation['attribute_value_translation_forms'] = [];
        $attributeValues = $node->getAttributeValues();
        /** @var AttributeValue $attributeValue */
        foreach ($attributeValues as $attributeValue) {
            /** @var FormFactory $formFactory */
            $formFactory = $this->get('formFactory');
            $name = $node->getNodeName() . '_attribute_' . $attributeValue->getId();
            $attributeValueTranslation = $attributeValue->getAttributeValueTranslation($translation);
            if (null === $attributeValueTranslation) {
                $attributeValueTranslation = new AttributeValueTranslation();
                $attributeValueTranslation->setAttributeValue($attributeValue);
                $attributeValueTranslation->setTranslation($translation);
            }
            $attributeValueTranslationForm = $formFactory->createNamedBuilder(
                $name,
                AttributeValueTranslationType::class,
                $attributeValueTranslation
            )->getForm();
            $attributeValueTranslationForm->handleRequest($request);

            if ($attributeValueTranslationForm->isSubmitted()) {
                if ($attributeValueTranslationForm->isValid()) {
                    $this->get('em')->merge($attributeValueTranslation);
                    $this->get('em')->flush();

                    /*
                     * Dispatch event
                     */
                    $event = new FilterNodesSourcesEvent($nodeSource);
                    $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_UPDATED, $event);

                    $msg = $this->getTranslator()->trans(
                        'attribute_value_translation.%name%.updated_from_node.%nodeName%',
                        [
                            '%name%' => $attributeValue->getAttribute()->getLabelOrCode($translation),
                            '%nodeName%' => $nodeSource->getTitle(),
                        ]
                    );
                    $this->publishConfirmMessage($request, $msg, $nodeSource);
                    return $this->redirect($this->generateUrl('nodesEditAttributesPage', [
                        'nodeId' => $node->getId(),
                        'translationId' => $translation->getId(),
                    ]));
                } else {
                    foreach ($this->getErrorsAsArray($attributeValueTranslationForm) as $error) {
                        $this->publishErrorMessage($request, $error);
                    }
                }
            }

            $this->assignation['attribute_value_translation_forms'][] = $attributeValueTranslationForm->createView();
        }

        $this->assignation['source'] = $nodeSource;
        $this->assignation['translation'] = $translation;
        $this->assignation['available_translations'] = $availableTranslations;
        $this->assignation['node'] = $node;

        return $this->render('nodes/attributes/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request     $request
     * @param Node        $node
     * @param Translation $translation
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
     */
    protected function handleAddAttributeForm(Request $request, Node $node, Translation $translation)
    {
        $attributeValue = new AttributeValue();
        $attributeValue->setAttributable($node);
        $addAttributeForm = $this->createForm(AttributeValueType::class, $attributeValue, [
            'entityManager' => $this->get('em'),
            'translation' => $this->get('defaultTranslation'),
        ]);
        $addAttributeForm->handleRequest($request);

        if ($addAttributeForm->isValid()) {
            $this->get('em')->persist($attributeValue);
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('nodesEditAttributesPage', [
                'nodeId' => $node->getId(),
                'translationId' => $translation->getId(),
            ]));
        }
        $this->assignation['addAttributeForm'] = $addAttributeForm->createView();

        return null;
    }

    /**
     * @param Request $request
     * @param         $nodeId
     * @param         $translationId
     * @param         $attributeValueId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request, $nodeId, $translationId, $attributeValueId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES_DELETE');

        /** @var AttributeValue $item */
        $item = $this->get('em')->find(AttributeValue::class, (int) $attributeValueId);
        if ($item === null) {
            throw $this->createNotFoundException('AttributeValue does not exist.');
        }
        /** @var Translation $translation */
        $translation = $this->get('em')->find(Translation::class, (int) $translationId);
        /** @var Node $node */
        $node = $this->get('em')->find(Node::class, (int) $nodeId);

        if (null === $translation || null === $node) {
            throw $this->createNotFoundException('Node-source does not exist');
        }

        /** @var NodesSources $nodeSource */
        $nodeSource = $this->get('em')
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findOneBy(['translation' => $translation, 'node' => $node]);

        if (null === $nodeSource) {
            throw $this->createNotFoundException('Node-source does not exist');
        }
        $availableTranslations = $this->get('em')
            ->getRepository(Translation::class)
            ->findAvailableTranslationsForNode($node);


        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->get('em')->remove($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'attribute.%name%.deleted_from_node.%nodeName%',
                    [
                        '%name%' => $item->getAttribute()->getLabelOrCode($translation),
                        '%nodeName%' => $nodeSource->getTitle(),
                    ]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('nodesEditAttributesPage', [
                'nodeId' => $node->getId(),
                'translationId' => $translation->getId(),
            ]));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;
        $this->assignation['source'] = $nodeSource;
        $this->assignation['translation'] = $translation;
        $this->assignation['available_translations'] = $availableTranslations;
        $this->assignation['node'] = $node;

        return $this->render('nodes/attributes/delete.html.twig', $this->assignation);
    }
}
