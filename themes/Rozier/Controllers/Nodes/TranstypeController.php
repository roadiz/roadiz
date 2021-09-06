<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Utils\Node\NodeTranstyper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\TranstypeType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class TranstypeController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $nodeId
     *
     * @return RedirectResponse|Response
     */
    public function transtypeAction(Request $request, int $nodeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /** @var Node|null $node */
        $node = $this->get('em')->find(Node::class, $nodeId);
        $this->get('em')->refresh($node);

        if (null === $node) {
            throw new ResourceNotFoundException();
        }

        $form = $this->createForm(TranstypeType::class, null, [
            'currentType' => $node->getNodeType(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var NodeType $newNodeType */
            $newNodeType = $this->get('em')->find(NodeType::class, (int) $data['nodeTypeId']);

            /** @var NodeTranstyper $transtyper */
            $transtyper = $this->get(NodeTranstyper::class);
            $transtyper->transtype($node, $newNodeType);
            $this->get('em')->flush();
            $this->get('em')->refresh($node);
            /*
             * Dispatch event
             */
            $this->get('dispatcher')->dispatch(new NodeUpdatedEvent($node));

            foreach ($node->getNodeSources() as $nodeSource) {
                $this->get('dispatcher')->dispatch(new NodesSourcesUpdatedEvent($nodeSource));
            }

            $msg = $this->getTranslator()->trans('%node%.transtyped_to.%type%', [
                '%node%' => $node->getNodeName(),
                '%type%' => $newNodeType->getName(),
            ]);
            $this->publishConfirmMessage($request, $msg, $node->getNodeSources()->first());

            return $this->redirect($this->generateUrl(
                'nodesEditSourcePage',
                [
                    'nodeId' => $node->getId(),
                    'translationId' => $node->getNodeSources()->first()->getTranslation()->getId(),
                ]
            ));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['node'] = $node;
        $this->assignation['parentNode'] = $node->getParent();
        $this->assignation['type'] = $node->getNodeType();

        return $this->render('nodes/transtype.html.twig', $this->assignation);
    }
}
