<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview;

use RZ\Roadiz\Core\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * BC Preview resolver to check Request-time then Kernel boot-time preview param.
 *
 * @package RZ\Roadiz\Preview
 */
final class KernelPreviewRevolver implements PreviewResolverInterface
{
    private KernelInterface $kernel;
    private RequestStack $requestStack;

    /**
     * @param KernelInterface $kernel
     * @param RequestStack $requestStack
     */
    public function __construct(KernelInterface $kernel, RequestStack $requestStack)
    {
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool
     */
    public function isPreview(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && $request instanceof PreviewAwareInterface) {
            return $request->isPreview() || $this->kernel->isPreview();
        }
        return $this->kernel->isPreview();
    }
}
