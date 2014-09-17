<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file LoggerHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\Logger;
use Doctrine\ORM\EntityManager;

/**
 * Handle operations with logs entities.
 */
class LoggerHandler
{
    private $log;

    /**
     * Create a new log handler with log to handle.
     * @param Logger $log
     */
    public function __construct(Logger $log)
    {
        $this->log =  $log;
    }

    /**
     * @return RZ\Renzo\Core\Handlers\LoggerHandler
     */
    public function persistAndFlush()
    {
        Kernel::getInstance()->em()->persist($this->log);
        Kernel::getInstance()->em()->flush();

        return $this;
    }
}
