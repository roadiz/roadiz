<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\KernelInterface;
use Symfony\Component\Console\Helper\Helper;

class LoggerHelper extends Helper
{
    protected KernelInterface $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->kernel instanceof ContainerAwareInterface ? $this->kernel->get('logger.cli') : null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'logger';
    }
}
