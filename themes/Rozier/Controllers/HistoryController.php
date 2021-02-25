<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
    ];

    /**
     * List all logs action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_LOGS');

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Log::class,
            [],
            ['datetime' => 'DESC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setDisplayingAllNodesStatuses(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['logs'] = $listManager->getEntities();
        $this->assignation['levels'] = static::$levelToHuman;

        return $this->render('history/list.html.twig', $this->assignation);
    }

    /**
     * List user logs action.
     *
     * @param Request $request
     * @param int $userId
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function userAction(Request $request, int $userId)
    {
        $this->denyAccessUnlessGranted(['ROLE_BACKEND_USER', 'ROLE_ACCESS_LOGS']);

        if (!($this->isGranted(['ROLE_ACCESS_USERS', 'ROLE_ACCESS_LOGS'])
            || ($this->getUser() instanceof User && $this->getUser()->getId() == $userId))) {
            throw new AccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }

        /** @var User|null $user */
        $user = $this->em()->find(User::class, $userId);

        if (null !== $user) {
            /*
             * Manage get request to filter list
             */
            $listManager = $this->createEntityListManager(
                Log::class,
                ['user' => $user],
                ['datetime' => 'DESC']
            );
            $listManager->setDisplayingNotPublishedNodes(true);
            $listManager->setDisplayingAllNodesStatuses(true);
            $listManager->setItemPerPage(30);
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['logs'] = $listManager->getEntities();
            $this->assignation['levels'] = static::$levelToHuman;
            $this->assignation['user'] = $user;

            return $this->render('history/list.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }
}
