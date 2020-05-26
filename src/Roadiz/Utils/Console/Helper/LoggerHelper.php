<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Monolog\Logger;
use RZ\Roadiz\Core\KernelInterface;
use Symfony\Component\Console\Helper\Helper;

class LoggerHelper extends Helper
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->kernel->get('logger.cli');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'logger';
    }
}
