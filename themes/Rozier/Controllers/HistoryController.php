<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file HistoryController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Log;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\EntityRequiredException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Display CMS logs.
 */
class HistoryController extends RozierApp
{

    /*
     * const EMERGENCY = 0;
     * const CRITICAL =  1;
     * const ALERT =     2;
     * const ERROR =     3;
     * const WARNING =   4;
     * const NOTICE =    5;
     * const INFO =      6;
     * const DEBUG =     7;
     * const LOG =       8;
     */
    public static $levelToHuman = array(
        Log::EMERGENCY => "emergency",
        Log::CRITICAL => "critical",
        Log::ALERT => "alert",
        Log::ERROR => "error",
        Log::WARNING => "warning",
        Log::NOTICE => "notice",
        Log::INFO => "info",
        Log::DEBUG => "debug",
        Log::LOG => "log"
    );

    /**
     * List all logs action.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->em(),
            'RZ\Renzo\Core\Entities\Log',
            array(),
            array('datetime'=> 'DESC')
        );
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['logs'] = $listManager->getEntities();
        $this->assignation['levels'] = static::$levelToHuman;

        return new Response(
            $this->getTwig()->render('history/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
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
        $user = $this->em()
                     ->find('RZ\Renzo\Core\Entities\User', (int) $userId);

        if (null !== $user) {
            /*
             * Manage get request to filter list
             */
            $listManager = new EntityListManager(
                $request,
                $this->em(),
                'RZ\Renzo\Core\Entities\Log',
                array('user'=>$user),
                array('datetime'=> 'DESC')
            );
            $listManager->setItemPerPage(30);
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['logs'] = $listManager->getEntities();
            $this->assignation['levels'] = static::$levelToHuman;

            return new Response(
                $this->getTwig()->render('history/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );

        } else {
            return $this->throw404();
        }
    }
}
