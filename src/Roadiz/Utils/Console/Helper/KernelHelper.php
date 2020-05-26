<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Helper\Helper;

/**
 * KernelHelper.
 */
class KernelHelper extends Helper
{
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kernel';
    }
}
