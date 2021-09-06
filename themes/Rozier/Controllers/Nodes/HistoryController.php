<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class HistoryController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $nodeId
     * @return Response
     */
    public function historyAction(Request $request, int $nodeId)
    {
        $this->denyAccessUnlessGranted(['ROLE_ACCESS_NODES', 'ROLE_ACCESS_LOGS']);
        /** @var Node|null $node */
        $node = $this->get('em')->find(Node::class, $nodeId);

        if (null === $node) {
            throw new ResourceNotFoundException();
        }

        $listManager = $this->createEntityListManager(
            Log::class,
            [
                'nodeSource' => $node->getNodeSources()->toArray(),
            ],
            ['datetime' => 'DESC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setDisplayingAllNodesStatuses(true);
        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('user_history_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);
        $listManager->handle();

        $this->assignation['node'] = $node;
        $this->assignation['translation'] = $this->get('defaultTranslation');
        $this->assignation['entries'] = $listManager->getEntities();
        $this->assignation['filters'] = $listManager->getAssignation();

        return $this->render('nodes/history.html.twig', $this->assignation);
    }
}
