<?php
/*
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
 * @file HistoryController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Themes\Rozier\RozierApp;

/**
 * Display CMS logs.
 */
class HistoryController extends RozierApp
{

    public static $levelToHuman = [
        Log::EMERGENCY => "emergency",
        Log::CRITICAL => "critical",
        Log::ALERT => "alert",
        Log::ERROR => "error",
        Log::WARNING => "warning",
        Log::NOTICE => "notice",
        Log::INFO => "info",
        Log::DEBUG => "debug",
        Log::LOG => "log",
    ];

    /**
     * List all logs action.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->em(),
            'RZ\Roadiz\Core\Entities\Log',
            [],
            ['datetime' => 'DESC']
        );
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['logs'] = $listManager->getEntities();
        $this->assignation['levels'] = static::$levelToHuman;

        return $this->render('history/list.html.twig', $this->assignation);
    }

    /**
     * List user logs action.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_USERS')
            || $this->getSecurityContext()->getToken()->getUser()->getId() == $userId)) {
            throw new AccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }

        $user = $this->em()
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if (null !== $user) {
            /*
             * Manage get request to filter list
             */
            $listManager = new EntityListManager(
                $request,
                $this->em(),
                'RZ\Roadiz\Core\Entities\Log',
                ['user' => $user],
                ['datetime' => 'DESC']
            );
            $listManager->setItemPerPage(30);
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['logs'] = $listManager->getEntities();
            $this->assignation['levels'] = static::$levelToHuman;
            $this->assignation['user'] = $user;

            return $this->render('history/list.html.twig', $this->assignation);

        } else {
            return $this->throw404();
        }
    }
}
