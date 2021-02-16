<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Helper\Helper;

class KernelHelper extends Helper
{
    private Kernel $kernel;

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
    public function getKernel(): Kernel
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
