<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
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
 * @file TranslateController.php
 * @author ambroisemaupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\Node\TranslateNodeType;
use Themes\Rozier\RozierApp;

/**
 * Class TranslateController
 * @package Themes\Rozier\Controllers\Nodes
 */
class TranslateController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $nodeId
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function translateAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        /** @var Node $node */
        $node = $this->get('em')
                     ->find(Node::class, (int) $nodeId);

        if (null !== $node) {
            $availableTranslations = $this->get('em')
                                 ->getRepository(Translation::class)
                                 ->findUnavailableTranslationsForNode($node);

            if (count($availableTranslations) > 0) {
                /** @var Form $form */
                $form = $this->createForm(TranslateNodeType::class, null, [
                    'em' => $this->get('em'),
                    'node' => $node,
                ]);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    /** @var Translation $translation */
                    $translation = $form->get('translation')->getData();
                    $translateOffsprint = (boolean) $form->get('translate_offspring')->getData();

                    try {
                        $this->doTranslate($translation, $node, $translateOffsprint);
                        $msg = $this->getTranslator()->trans('node.%name%.translated', [
                            '%name%' => $node->getNodeName(),
                        ]);
                        $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());
                    } catch (EntityAlreadyExistsException $e) {
                        $this->publishErrorMessage($request, $e->getMessage(), $node->getNodeSources()->first());
                    }

                    return $this->redirect($this->generateUrl(
                        'nodesEditSourcePage',
                        ['nodeId' => $node->getId(), 'translationId' => $translation->getId()]
                    ));
                }
                $this->assignation['form'] = $form->createView();
            }

            $this->assignation['node'] = $node;
            $this->assignation['translation'] = $this->get('defaultTranslation');

            $this->assignation['available_translations'] = [];

            foreach ($node->getNodeSources() as $ns) {
                $this->assignation['available_translations'][] = $ns->getTranslation();
            }

            return $this->render('nodes/translate.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Create a new node-source for given translation.
     *
     * @param Translation $translation
     * @param Node $node
     */
    protected function translateNode(Translation $translation, Node $node)
    {
        $existing = $this->get('em')
                         ->getRepository(NodesSources::class)
                         ->setDisplayingAllNodesStatuses(true)
                         ->setDisplayingNotPublishedNodes(true)
                         ->findOneByNodeAndTranslation($node, $translation);
        if (null === $existing) {
            $baseSource = $node->getNodeSources()->first();
            if (null !== $baseSource && $baseSource instanceof NodesSources) {
                $source = clone $baseSource;

                foreach ($source->getDocumentsByFields() as $document) {
                    $this->get('em')->persist($document);
                }
                $source->setTranslation($translation);
                $source->setNode($node);

                $this->get('em')->persist($source);

                /*
                 * Dispatch event
                 */
                $event = new FilterNodesSourcesEvent($source);
                $this->get('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_CREATED, $event);
            }
        }
    }

    /**
     * @param Translation $translation
     * @param Node $node
     * @param bool $translateChildren
     */
    protected function doTranslate(Translation $translation, Node $node, $translateChildren = false)
    {
        $this->translateNode($translation, $node);

        if ($translateChildren) {
            foreach ($node->getChildren() as $child) {
                $this->doTranslate($translation, $child, $translateChildren);
            }
        }

        $this->get('em')->flush();
    }
}
