<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
use Twig\Error\RuntimeError;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class TranslateController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $nodeId
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     */
    public function translateAction(Request $request, int $nodeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node $node */
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
                    /** @var Translation $destinationTranslation */
                    $destinationTranslation = $form->get('translation')->getData();
                    /** @var Translation $sourceTranslation */
                    $sourceTranslation = $form->get('sourceTranslation')->getData();
                    $translateOffspring = (bool) $form->get('translate_offspring')->getData();

                    try {
                        $this->doTranslate($sourceTranslation, $destinationTranslation, $node, $translateOffspring);
                        $msg = $this->getTranslator()->trans('node.%name%.translated', [
                            '%name%' => $node->getNodeName(),
                        ]);
                        $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());
                        return $this->redirect($this->generateUrl(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId' => $destinationTranslation->getId()]
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
     * @param Translation $sourceTranslation
     * @param Translation $destinationTranslation
     * @param Node $node
     * @throws ORMException
     */
    protected function translateNode(
        Translation $sourceTranslation,
        Translation $destinationTranslation,
        Node $node
    ) {
        $existing = $this->em()
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findOneByNodeAndTranslation($node, $destinationTranslation);

        if (null === $existing) {
            /** @var NodesSources|false $baseSource */
            $baseSource =
                $node->getNodeSourcesByTranslation($sourceTranslation)->first() ?:
                    $node->getNodeSources()->filter(function (NodesSources $nodesSources) {
                        return $nodesSources->getTranslation()->isDefaultTranslation();
                    })->first() ?:
                        $node->getNodeSources()->first();

            if (!($baseSource instanceof NodesSources)) {
                throw new \RuntimeException('Cannot translate a Node without any NodesSources');
            }

            $source = clone $baseSource;

            foreach ($source->getDocumentsByFields() as $document) {
                $this->em()->persist($document);
            }
            $source->setTranslation($destinationTranslation);
            $source->setNode($node);

            $this->em()->persist($source);
            /*
             * Dispatch event
             */
            $this->get('dispatcher')->dispatch(new NodesSourcesCreatedEvent($source));
        }
    }

    /**
     * @param Translation $sourceTranslation
     * @param Translation $destinationTranslation
     * @param Node $node
     * @param bool $translateChildren
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function doTranslate(
        Translation $sourceTranslation,
        Translation $destinationTranslation,
        Node $node,
        bool $translateChildren = false
    ) {
        $this->translateNode($sourceTranslation, $destinationTranslation, $node);

        if ($translateChildren) {
            /** @var Node $child */
            foreach ($node->getChildren() as $child) {
                $this->doTranslate($sourceTranslation, $destinationTranslation, $child, $translateChildren);
            }
        }

        $this->em()->flush();
    }
}
