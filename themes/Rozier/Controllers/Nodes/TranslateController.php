<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesCreatedEvent;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\Node\TranslateNodeType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class TranslateController extends RozierApp
{
    /**
     * @param Request $request
     * @param int     $nodeId
     *
     * @return Response
     */
    public function translateAction(Request $request, int $nodeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node|null $node */
        $node = $this->get('em')->find(Node::class, $nodeId);

        if (null !== $node) {
            $availableTranslations = $this->get('em')
                                 ->getRepository(Translation::class)
                                 ->findUnavailableTranslationsForNode($node);

            if (count($availableTranslations) > 0) {
                $form = $this->createForm(TranslateNodeType::class, null, [
                    'node' => $node,
                ]);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    /** @var Translation $translation */
                    $translation = $form->get('translation')->getData();
                    $translateOffspring = (bool) $form->get('translate_offspring')->getData();

                    try {
                        $this->doTranslate($translation, $node, $translateOffspring);
                        $msg = $this->getTranslator()->trans('node.%name%.translated', [
                            '%name%' => $node->getNodeName(),
                        ]);
                        $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());
                        return $this->redirect($this->generateUrl(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId' => $translation->getId()]
                        ));
                    } catch (EntityAlreadyExistsException $e) {
                        $form->addError(new FormError($e->getMessage()));
                    }
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
            if ($baseSource instanceof NodesSources) {
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
                $this->get('dispatcher')->dispatch(new NodesSourcesCreatedEvent($source));
            }
        }
    }

    /**
     * @param Translation $translation
     * @param Node $node
     * @param bool $translateChildren
     */
    protected function doTranslate(Translation $translation, Node $node, bool $translateChildren = false)
    {
        $this->translateNode($translation, $node);

        if ($translateChildren) {
            /** @var Node $child */
            foreach ($node->getChildren() as $child) {
                $this->doTranslate($translation, $child, $translateChildren);
            }
        }

        $this->get('em')->flush();
    }
}
