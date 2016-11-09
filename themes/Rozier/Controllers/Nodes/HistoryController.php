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
 * @file HistoryController.php
 * @author ambroisemaupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Class HistoryController
 * @package Themes\Rozier\Controllers\Nodes
 */
class HistoryController extends RozierApp
{
    /**
     * @param Request $request
     * @param $nodeId
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function historyAction(Request $request, $nodeId, $page = 1)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');
        /** @var Node $node */
        $node = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null === $node) {
            return $this->throw404();
        }

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Log',
            [
                'nodeSource' => $node->getNodeSources()->toArray(),
            ],
            ['datetime' => 'DESC']
        );
        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('user_history_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);
        $listManager->handle();
        $listManager->setPage($page);

        $this->assignation['node'] = $node;
        $this->assignation['translation'] = $this->get('defaultTranslation');
        $this->assignation['entries'] = $listManager->getEntities();
        $this->assignation['filters'] = $listManager->getAssignation();

        return $this->render('nodes/history.html.twig', $this->assignation);
    }
}
